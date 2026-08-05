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
                it(
                    'Destroys an existing log file when append mode is explicitly disabled',
                    function () {
                        $test_dir = srw_test_log_dir();
                        $target_file = $test_dir . '/destructive_test.log';
                        
                        // Create a file ahead of time
                        if (!is_dir($test_dir)) mkdir($test_dir, 0755, true);
                        file_put_contents($target_file, 'Pre-existing log state.');
                        
                        // Instantiate with append set to false
                        new SRW_Log(
                            name: 'destructive_test',
                            directory: $test_dir,
                            is_family: false,
                            append: false
                        );
                        
                        expect($target_file)->not->toBeFile();
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
                it(
                    'Splits extremely long text strings into manageable padded chunks',
                    function () {
                        // Artificially change configurations to low message sizing steps
                        \SRW\Logger\Public\Classes\SRW_Logger::$max_message_size = 10;
                        
                        $this->log_worker->write(
                            message: '1234567890ABCDEFGHIJ',
                            data: null,
                            level: SRW_Log_Level::Info,
                            component_context: 'ChunkTest'
                        );
                        
                        $contents = file_get_contents($this->logFile);
                        expect($contents)->toContain('...')->and($contents)->toContain('1234567890');
                        
                        // Restore sizing balance configurations
                        \SRW\Logger\Public\Classes\SRW_Logger::$max_message_size = 5000;
                    }
                );
                it(
                    'Executes file renames and moves data during file overflow events',
                    function () {
                        \SRW\Logger\Public\Classes\SRW_Logger::setup(srw_test_log_dir(), true, 10, 1000); // 10-byte limit
                        $log_worker = new SRW_Log('overflow_run', srw_test_log_dir(), false, true);
                        
                        // Write something to establish a physical file size baseline
                        $log_worker->write('A', null, SRW_Log_Level::Info); 
                        
                        // This write triggers test_and_roll_over(), matches file_exists, hits the size check, and runs lines 193-208
                        $log_worker->write('B', null, SRW_Log_Level::Info);
                        
                        $rotated_files = glob(srw_test_log_dir() . '/overflow_run-*.log');
                        expect($rotated_files)->not->toBeEmpty();

                    }
                );

            }
        );
    }
);
