<?php
declare(
    strict_types=
        1
);

namespace Tests\Integration\Public\Classes;

use ReflectionClass;
use SRW\Logger\Public\Classes\SRW_Logger;


integration_group(
    function(
    ) {
        describe(
            "Verifies zero-configuration initialization and directory isolation security",
            function(
            ) {
                beforeEach(
                    function(
                    ) {
                        SRW_Logger::
                            setup(
                                null
                            );
                        $this->
                            resolved_path = 
                                new ReflectionClass(
                                    SRW_Logger::
                                        class
                                )->
                                    getProperty(
                                        'path'
                                    )->
                                        getValue(                    
                                        );
                    }
                );
                it(
                    "Creates Directory",
                    fn() =>
                        expect(
                            $this->
                                resolved_path
                        )->
                            toBeDirectory(
                            )
                );
                it(
                    "Creates Files",
                    fn(
                        string $file
                    ) =>
                        expect(
                            $this->
                                resolved_path .
                                    $file
                        )->
                            toBeFile(
                            )
                )->
                    with(
                        named_dataset(
                            [
                                '/.htaccess',
                                '/index.html'
                            ],
                            fn(
                                string $f
                            ) =>
                                "Creates file {$f}"
                        )
                    );
            }
        );
        describe(
            "Verifies stack tracing works and writes to a log file",
            function(
            ) {
                beforeEach(
                    function(
                    ) {
                        SRW_Logger::
                            setup(
                                sys_get_temp_dir(                    
                                )
                            );
                        SRW_Logger::
                            info(
                                "Testing standard string output payload assertions."
                            );
                        $this->
                            generated_files = 
                                glob(
                                    sys_get_temp_dir(                    
                                    ) . 
                                        '/*.log'
                                );
                        $this->
                            contents = 
                                file_get_contents(
                                    $this->
                                        generated_files[
                                            0
                                        ]
                                );
                    }
                );
                it(
                    "The logger engine writes to file inside the sandbox.",
                    fn() =>
                        expect(
                            $this->
                                generated_files
                        )->
                            not->
                                toBeEmpty(
                                )
                );
                it(
                    "The log file contains",
                    fn(
                        string $value
                    ) =>
                        expect(
                            $this->
                                contents
                        )->
                            toContain(
                                $value
                            )
                )->
                    with(
                        named_dataset(
                            [
                                '[INFO]', 
                                'Testing standard string output payload assertions.'
                            ],
                            fn(
                                string $c
                            ) =>
                                "the log file contains '{$c}'"
                        )
                    );
            }
        );
        describe(
            "Verifies the Log Retention Policy Purger accurately drops expired logs",
            function(
            ) {
                beforeEach(
                    function(
                    ) {
                        SRW_Logger::
                            setup(
                                sys_get_temp_dir(                    
                                )                            
                            );
                        $this->
                            active_file = 
                                sys_get_temp_dir(                    
                                ) . 
                                    '/active_module_stream.log';
                        file_put_contents(
                            $this->
                                active_file, 
                                    'Active logging channel.'
                        );
                        $this->
                            fresh_rotated_file = 
                                sys_get_temp_dir(                    
                                ) . 
                                    '/active_module_stream-' . 
                                        date(
                                            'Ymd-His'
                                        ) . 
                                            '.log';
                        file_put_contents(
                            $this->
                                fresh_rotated_file, 
                            'Recent archived snapshot.'
                        );
                        $this->
                            expired_rotated_file = 
                                sys_get_temp_dir(                    
                                ) . 
                                    '/expired_module_stream-20200101-120000.log';
                        file_put_contents(
                            $this->
                                expired_rotated_file, 
                            'Ancient legacy troubleshooting traces.'
                        );
                        touch(
                            $this->
                                expired_rotated_file, 
                            time(                
                            ) - 
                                (
                                    5 * 
                                        365 * 
                                            86400
                                )
                        );
                        new ReflectionClass(
                                SRW_Logger::
                                    class
                            )->
                                getMethod(
                                    'purge_old_logs'
                                )->
                                    invoke(
                                        null
                                    );
                    }
                );
                it(
                    "Active File exists",
                    fn(                    
                    ) =>
                        expect(
                            $this->
                                active_file
                        )->
                            toBeFile(
                            )
                );
                it(
                    "Fresh Rotated File exists",
                    fn(
                    ) =>
                        expect(
                            $this->
                                fresh_rotated_file
                        )->
                            toBeFile(
                            )
                );
                it(
                    "File does not exist",
                    fn() =>
                        expect(
                            $this->
                                expired_rotated_file
                        )->
                            not->
                                toBeFile(
                                )
                );
            }
        );
    }
);
