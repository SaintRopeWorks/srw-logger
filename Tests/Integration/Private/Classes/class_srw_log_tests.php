<?php declare(strict_types=1);

namespace Tests\Integration\Private\Classes;

use Tests\SRWTestCaseBase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use ReflectionClass;
use ReflectionMethod;
use SRW\Logger\Public\Classes\SRW_Logger;
use SRW\Logger\Public\Attributes\LogFamily;

#[TestDox('SRW_Log Class (Integration)')]
class class_srw_log_tests extends SRWTestCaseBase {

    #[Test]
    #[TestDox("Verifies dynamic attribute resolution mapping using the 'global' resolver pattern")]
    public function test_attribute_log_family_resolves_global_variables_correctly(): void {
        SRW_Logger::clear_calls();
        SRW_Logger::setup($this->test_log_dir);
        $GLOBALS['test_active_pipeline'] = 'Integration_Suite_Family';

        $reflector = new ReflectionMethod(SRW_Test_Mock_Runner::class, 'execute_mock_lambda');
        $logger_reflection = new ReflectionClass(SRW_Logger::class);
        $extract_method = $logger_reflection->getMethod('extract_families_from_reflector');
        $extract_method->setAccessible(true);
        $discovered_families = $extract_method->invoke(null, $reflector);

        $GLOBALS['srw_mock_caller_context'] = [
            'target_name' => 'SRW_Test_Mock_Runner_execute_mock_lambda',
            'basename' => 'class_srw_log_tests.php',
            'line' => 150,
            'resolved_function' => 'SRW_Test_Mock_Runner::execute_mock_lambda()',
            'families' => $discovered_families
        ];

        SRW_Logger::warn("Triggering pipeline alert.");

        $expected_family_file = $this->test_log_dir . '/Integration_Suite_Family.log';
        $this->assertFileExists($expected_family_file);
        
        $contents = file_get_contents($expected_family_file);
        $this->assertStringContainsString('[WARN]', $contents);
        $this->assertStringContainsString('SRW_Test_Mock_Runner::execute_mock_lambda()', $contents);
        $this->assertStringContainsString('Triggering pipeline alert.', $contents);
    }

    #[Test]
    #[TestDox("Test Case 5: Verifies the engine climbs past recursive closures and loops")]
    public function test_stack_tracer_bypasses_closures_and_identifies_named_recursive_functions(): void {
        SRW_Logger::setup($this->test_log_dir);
        $recursive_runner = new SRW_Test_Recursive_Runner();
        $recursive_runner->trigger_recursive_loop(3);

        $expected_file = $this->test_log_dir . '/SRW_Test_Recursive_Runner_trigger_recursive_loop.log';
        $this->assertFileExists($expected_file);
        
        $contents = file_get_contents($expected_file);
        $this->assertStringContainsString('SRW_Test_Recursive_Runner::trigger_recursive_loop()', $contents);
        $this->assertStringContainsString('Processing recursive depth level: 3', $contents);
    }

    #[Test]
    #[TestDox("Test Case 6: Verifies relative sub-path parameters map safely")]
    public function test_relative_log_family_path_creates_nested_subfolders_safely(): void {
        SRW_Logger::setup($this->test_log_dir);
        $discovered_families = [['name' => 'Integration_Suite_Family', 'path' => 'nested/sub/folder']];

        $GLOBALS['srw_mock_caller_context'] = [
            'target_name' => 'SRW_Test_Mock_Runner_execute_mock_lambda',
            'basename' => 'class_srw_log_tests.php',
            'line' => 210,
            'resolved_function' => 'SRW_Test_Mock_Runner::execute_mock_lambda()',
            'families' => $discovered_families
        ];

        SRW_Logger::info("Testing nested subdirectory allocation.");

        $expected_nested_dir = $this->test_log_dir . '/nested/sub/folder';
        $this->assertDirectoryExists($expected_nested_dir);
        
        $expected_file = $expected_nested_dir . '/Integration_Suite_Family.log';
        $this->assertFileExists($expected_file);
    }
}

/**
 * Isolated Symmetrical Test Mocks & Tracing Loops
 */
class SRW_Test_Mock_Runner {
    #[LogFamily(family: 'test_active_pipeline', resolver: 'global')]
    public function execute_mock_lambda(): void {
        SRW_Logger::warn("Triggering pipeline alert.");
    }
}

class SRW_Test_Recursive_Runner {
    public function trigger_recursive_loop(int $depth): void {
        if ($depth <= 0) return;
        $array_mock = [0];
        array_map(function($item) use ($depth) {
            SRW_Logger::info("Processing recursive depth level: " . $depth);
            $this->trigger_recursive_loop($depth - 1);
        }, $array_mock);
    }
}
