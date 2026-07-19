<?php
declare(
    strict_types =
        1
);

namespace Tests;

use PHPUnit\Framework\TestCase;

abstract class SRWTestCaseBase extends TestCase {
    protected string $test_log_dir;

    protected function setUp(        
    ): void {
        parent::
            setUp(
            );
        $this->
            test_log_dir = 
                sys_get_temp_dir(                    
                ) 
                    . '/srw_test_logs';
        if (
            !file_exists(
                $this->
                    test_log_dir
            )
        ) {
            mkdir(
                $this->
                    test_log_dir, 
                    0755, 
                    true
            );
        }
    }

    protected function tearDown(        
    ): void {
        if (
            file_exists(
                $this->
                    test_log_dir
            )
        ) {
            $files = 
                glob(
                    $this->
                        test_log_dir 
                            . '/*'
                );
            foreach (
                $files as 
                    $file
            ) {
                if (
                    is_file(
                        $file
                    )
                ) {
                    if (
                        is_dir(
                            $file
                        )
                    ) {
                        $subfiles = 
                            glob(
                                $file 
                                    . '/*'
                            );
                        foreach (
                            $subfiles as 
                                $sf
                        ) 
                            @unlink(
                                $sf
                            );
                        @rmdir(
                            $file
                        );
                    } else {
                        @unlink(
                            $file
                        );
                    }                    
                }
            }
            @rmdir(
                $this->
                    test_log_dir
            );
        }

        unset(
            $GLOBALS[
                'srw_mock_caller_context'
            ]
        );
        unset(
            $GLOBALS[
                'test_active_pipeline'
            ]
        );
        parent::
            tearDown(                
            );
    }
}
