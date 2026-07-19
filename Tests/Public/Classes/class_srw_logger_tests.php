<?php
declare(
    strict_types=
        1
);

namespace Tests\Public\Classes;

use Tests\SRWTestCaseBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use ReflectionClass;
use SRW_Logger;

#[
    TestDox(
        'SRW_Logger Class'
    )
]
class class_srw_logger_tests extends SRWTestCaseBase {
    #[
        Test
    ]
    #[
        TestDox(
            "Verifies zero-configuration initialization and directory isolation security"
        )
    ]
    public function test_logger_setup_creates_secure_directory_and_shields(        
    ): void {
        SRW_Logger::
            setup(
                null
            );
        $reflection = 
            new ReflectionClass(
                SRW_Logger::
                    class
            );
        $path_property = 
            $reflection->
                getProperty(
                    'path'
                );
        $resolved_path = 
            $path_property->
                getValue(                    
                );
        $this->
            assertDirectoryExists(
                $resolved_path
            );
        $this->
            assertFileExists(
                $resolved_path 
                    . '/.htaccess'
            );
        $this->
            assertFileExists(
                $resolved_path 
                    . '/index.html'
            );
    }

    #[
        Test
    ]
    #[
        TestDox(
            "Verifies stack tracing works and writes to a log file"
        )
    ]
    public function test_logger_routes_to_correct_file_matching_execution_unit(        
    ): void {
        SRW_Logger::
            setup(
                $this->
                    test_log_dir
            );
        SRW_Logger::
            info(
                "Testing standard string output payload assertions."
            );

        $generated_files = 
            glob(
                $this->
                    test_log_dir 
                        . '/*.log'
            );
        $this->
            assertNotEmpty(
                $generated_files, 
                "The logger engine failed to write any file inside the sandbox."
            );
        $contents = 
            file_get_contents(
                $generated_files[
                    0
                ]
            );
        $this->
            assertStringContainsString(
                '[INFO]', 
                $contents
            );
        $this->
            assertStringContainsString(
                'Testing standard string output payload assertions.', 
                $contents
            );
    }

    #[
        Test
    ]
    #[
        TestDox(
            "Verifies the Log Retention Policy Purger accurately drops expired logs"
        )
    ]
    public function test_log_retention_purges_expired_rotated_files_only(        
    ): void {
        SRW_Logger::
            setup(
                $this->
                    test_log_dir
            );
        $active_file = 
            $this->
                test_log_dir 
                    . '/active_module_stream.log';
        file_put_contents(
            $active_file, 
            'Active logging channel.'
        );
        $fresh_rotated_file = 
            $this->
                test_log_dir 
                    . '/active_module_stream-' 
                    . date(
                        'Ymd-His'
                    ) 
                    . '.log';
        file_put_contents(
            $fresh_rotated_file, 
            'Recent archived snapshot.'
        );
        $expired_rotated_file = 
            $this->
                test_log_dir 
                    . '/expired_module_stream-20200101-120000.log';
        file_put_contents(
            $expired_rotated_file, 
            'Ancient legacy troubleshooting traces.'
        );
        touch(
            $expired_rotated_file, 
            time(                
            ) - 
                (
                    5 * 
                        365 * 
                            86400
                )
        );
        $reflection = 
            new ReflectionClass(
                SRW_Logger::
                    class
            );
        $method = 
            $reflection->
                getMethod(
                    'purge_old_logs'
                );
        $method->
            setAccessible(
                true
            );
        $method->
            invoke(
                null
            );
        $this->
            assertFileExists(
                $active_file
            );
        $this->
            assertFileExists(
                $fresh_rotated_file
            );
        $this->
            assertFileDoesNotExist(
                $expired_rotated_file
            );
    }
}
