<?php 

declare(
    strict_types =
        1
);

namespace Tests\Unit\Private\Classes;

use SRW\Logger\Private\Classes\SRW_Log;
use SRW\Logger\Private\Enumerations\SRW_Log_Level;


unit_group(
    function(
    ) {
        describe(
            'Constructor',
            function(                
            ) {
                it(
                   'Verifies the constructor cleans invalid filename characters and populates attributes',
                   function(
                   ) {
                        $log_worker = 
                            new SRW_Log(
                                name: 
                                    'User<Profile>Input*"Matrix"', 
                                directory: 
                                    sys_get_temp_dir(                    
                                    ) . 
                                        '/srw_test_logs', 
                                is_family: 
                                    true, 
                                append: 
                                    true
                            );
                        expect(
                    $log_worker->
                            is_family(
                            )
                        )->
                            ToBeTrue(
                            );
                        expect(
                        $log_worker->
                            get_name(
                            )
                        )->
                            ToBe(
                                'User_Profile_Input*_Matrix_'
                            );
                    }
                );
            }
        );
        describe(
            'File Stuff',
            function(
            ) {
                beforeEach(
                    function(
                    ) {
                        $this->
                            LogName =
                                'direct_unit_stream';
                        $this->
                            is_family  =
                                false;
                        $this->
                            append =
                                false;
                        $this->
                            test_log_dir = 
                                sys_get_temp_dir(                    
                                ) . 
                                    '/srw_test_logs';
                        $this->
                            logFile =
                                $this->
                                    test_log_dir .
                                        '/' .
                                        $this->
                                            LogName .
                                        '.log';
                        $this->
                            log_worker = 
                                new SRW_Log(
                                    name: 
                                        $this->
                                            LogName, 
                                    directory: 
                                        $this->
                                            test_log_dir, 
                                    is_family: 
                                        $this->
                                            is_family, 
                                    append: 
                                        $this->
                                            append
                                );
                    }
                );
                afterEach(
                    function(
                    ) {
                        if (
                            is_file(
                                $this->
                                    logFile                           
                            )
                        ) 
                            @unlink(
                                $this->
                                    logFile
                            );
                        unset(
                            $this->
                                log_worker
                        );
                    }
                );
                it(
                    'Log File Lifecycle',
                    function(                
                    ) {
                        expect(
                            $this->
                                logFile
                        )->
                            Not(
                            )->
                                ToBeFile(                        
                                );
                        $this->
                            log_worker->
                                write(
                                    message: 
                                        'Direct unit test bypass verification payload.',
                                    data: 
                                        [
                                            'debug_key' => 
                                                'verified_unit'
                                        ],
                                    level: 
                                        SRW_Log_Level::
                                            Warn,
                                    component_context: 
                                        'PureUnitTestContext'
                                );
                        expect(
                            $this->
                                logFile
                        )->
                            ToBeFile(                        
                            );
                    }
                );                
                it(
                    'Writes to the log file',
                    function(           
                        string $searchString
                    ) {
                        $this->
                            log_worker->
                                write(
                                    message: 
                                        'Direct unit test bypass verification payload.',
                                    data: 
                                        [
                                            'debug_key' => 
                                                'verified_unit'
                                        ],
                                    level: 
                                        SRW_Log_Level::
                                            Warn,
                                    component_context: 
                                        'PureUnitTestContext'
                                );
                        $contents = 
                            file_get_contents(
                                $this->
                                    logFile
                            );
                        expect(
                            $contents
                        )->
                            toContain(
                                $searchString
                            );
                    }
                )->
                    with(
                        [
                            '[WARN]', 
                            'SRW_Log::new_log_line()',
                            'verified_unit', 
                        ]
                    );
            }
        );
    }
);
