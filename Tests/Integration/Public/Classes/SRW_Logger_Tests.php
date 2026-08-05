<?php
declare(strict_types=1);

namespace Tests\Integration\Public\Classes;

use ReflectionClass;
use ReflectionFunction;
use SRW\Logger\Public\Classes\SRW_Logger;

integration_group(
    function() {
        describe(
            "Verifies zero-configuration initialization and directory isolation security",
            function() {
                beforeEach(
                    function() {
                        unset($GLOBALS['srw_mock_cli_mode']);
                        unset($GLOBALS['wp_mock_options']);
                        // FORCE INTERNAL ENGINE CONFIGURATION CACHE RESET
                        $reflector = new ReflectionClass(SRW_Logger::class);
                        $pathProp = $reflector->getProperty('path');
                        $pathProp->setValue(null, '');

                        $registryProp = $reflector->getProperty('_logger_registry');
                        $registryProp->setValue(null, []);

                        // Call setup with null to let it extract paths from your tests-bootstrap.php stub
                        SRW_Logger::setup(null);
                        
                        // Capture the exact location computed by the engine
                        $this->resolved_path = SRW_Logger::$path;
                    }
                );
                afterEach(
                    function() {
                        // Safely scrub any generated test files from the filesystem
                        if (!empty($this->resolved_path) && is_dir($this->resolved_path)) {
                            @unlink($this->resolved_path . '/.htaccess');
                            @unlink($this->resolved_path . '/index.html');
                            @rmdir($this->resolved_path);
                        }
                    }
                );
                it(
                    "Creates Directory", 
                    fn() => 
                        expect(
                            $this->resolved_path
                        )->toBeDirectory());
                it(
                    "Creates Files", 
                    fn(string $file) => 
                    expect(
                        $this->resolved_path . $file
                    )->
                        toBeFile()
                )->
                    with(
                        named_dataset(
                            ['/.htaccess', '/index.html'], 
                            fn(string $f) => "Creates file {$f}"
                        )
                    );
            }
        );

        describe(
            "Verifies stack tracing works and writes to a log file",
            function() {
                beforeEach(
                    function() {
                        SRW_Logger::setup(srw_test_log_dir());
                        SRW_Logger::info("Testing standard string output payload assertions.");
                        $this->generated_files = glob(srw_test_log_dir() . '/*.log');
                        $this->contents = !empty($this->generated_files) ? file_get_contents($this->generated_files[0]) : '';
                    }
                );
                it("The logger engine writes to file inside the sandbox.", fn() => expect($this->generated_files)->not->toBeEmpty());
                it("The log file contains", fn(string $value) => expect($this->contents)->toContain($value))->
                    with(named_dataset(['[INFO]', 'Testing standard string output payload assertions.'], fn(string $c) => "the log file contains '{$c}'"));
            }
        );

        describe(
            "Verifies the Log Retention Policy Purger accurately drops expired logs",
            function() {
                beforeEach(
                    function() {
                        SRW_Logger::setup(srw_test_log_dir());
                        $this->active_file = srw_test_log_dir() . '/active_module_stream.log';
                        file_put_contents($this->active_file, 'Active logging channel.');
                        
                        $this->fresh_rotated_file = srw_test_log_dir() . '/active_module_stream-' . date('Ymd-His') . '.log';
                        file_put_contents($this->fresh_rotated_file, 'Recent archived snapshot.');
                        
                        $this->expired_rotated_file = srw_test_log_dir() . '/expired_module_stream-20200101-120000.log';
                        file_put_contents($this->expired_rotated_file, 'Ancient legacy troubleshooting traces.');
                        touch($this->expired_rotated_file, time() - (5 * 365 * 86400));
                        
                        new ReflectionClass(SRW_Logger::class)->getMethod('purge_old_logs')->invoke(null);
                    }
                );
                it("Active File exists", fn() => expect($this->active_file)->toBeFile());
                it("Fresh Rotated File exists", fn() => expect($this->fresh_rotated_file)->toBeFile());
                it("File does not exist", fn() => expect($this->expired_rotated_file)->not->toBeFile());
            }
        );

        describe(
            "Verifies engine trace resolutions and attribute mapping boundaries",
            function () {
                beforeEach(function () {
                    unset($GLOBALS['srw_mock_cli_mode']);
                    
                    $reflector = new ReflectionClass(SRW_Logger::class);
                    $property = $reflector->getProperty('_call_stack');
                    $property->setValue(null, null);

                    $registryProp = $reflector->getProperty('_logger_registry');
                    $registryProp->setValue(null, []);

                    SRW_Logger::clear_calls();
                    unset($_SERVER['SCRIPT_FILENAME']);
                    $GLOBALS['wp_mock_options'] = [];
                    
                    SRW_Logger::setup(srw_test_log_dir());
                });

                it("Falls back to hardcoded default script filenames when server properties are absent", function () {
                    if (!function_exists('srw_standalone_test_tracer')) {
                        function srw_standalone_test_tracer() {
                            SRW_Logger::info("Tracing a global procedural function context.");
                        }
                    }
                    
                    srw_standalone_test_tracer();
                    $files = glob(srw_test_log_dir() . '/*standalone*.log');
                    expect($files)->not->toBeEmpty();
                });

                it("Evaluates option and constant parameter resolution mechanisms", function () {
                    $GLOBALS['wp_mock_options']['resolved_by_option'] = 'target_option_stream';
                    if (!defined('RESOLVED_BY_CONST')) {
                        define('RESOLVED_BY_CONST', 'target_const_stream');
                    }

                    $classMock = new class {
                        #[\SRW\Logger\Public\Attributes\LogFamily(family: 'resolved_by_option', resolver: 'option')]
                        #[\SRW\Logger\Public\Attributes\LogFamily(family: 'RESOLVED_BY_CONST', resolver: 'constant')]
                        public function triggerCustomMap() {
                            SRW_Logger::info("Testing attribute resolution loops.");
                        }
                    };

                    $classMock->triggerCustomMap();
                    
                    $target_file = srw_test_log_dir() . '/target_option_stream.log';
                    expect($target_file)->toBeFile();
                });
                
                it("Enforces retention overrides during zero or negative policy checks", function () {
                    $GLOBALS['wp_mock_options']['srw_log_retention_days'] = -5;
                    SRW_Logger::setup(srw_test_log_dir());
                    expect(SRW_Logger::$path)->toBe(srw_test_log_dir());
                });
            }
        );
                describe(
            "Verifies engine call stack parsing and unrecognized attribute default strategies",
            function () {
                it("Gracefully skips empty frames or blacklisted execution helper loops", function () {
                    // 1. GAP 2 RESOLUTION: Run the logging request inside an explicitly blacklisted wrapper framework
                    // This forces debug_backtrace() to encounter 'call_user_func', triggering lines 584-606
                    call_user_func(function() {
                        SRW_Logger::info("Logging inside a blacklisted wrapper runtime execution frame context.");
                    });

                    // 2. GAP 1 RESOLUTION: To force the loop to read a frame that is missing its function key entirely,
                    // we dynamically override get_calls via reflection to return an un-keyed array structure properties mask
                    $reflector = new \ReflectionClass(SRW_Logger::class);
                    
                    $reflector->
                        getProperty('_call_stack')->
                        setValue(
                            null, 
                            [
                                [
                                    // Intentionally omit 'function' completely to trigger lines 579-583 fallback string assignment
                                    'target_name' => 'mocked_unkeyed_stream',
                                    'basename' => 'index.php',
                                    'line' => 24,
                                    'resolved_function' => '',
                                    'families' => []
                                ]
                            ]
                        );
                    
                    // Trigger line compiling logic directly using the modern, non-deprecated fluent reflection invocation style
                    $result = $reflector->getMethod('get_caller_context')->invoke(null);
                    
                    expect($result)->toBeArray();
                    
                    // Always reset your static cache register to pristine defaults
                    $reflector->getProperty('_call_stack')->setValue(null, null);
                });

                it("Falls back to standard scalar data names when encountering an unrecognized attribute resolver string", function () {
                    // Use modern, fluently chained reflections to test the default match branch (Lines 761-763)
                    $classMock = new class {
                        #[\SRW\Logger\Public\Attributes\LogFamily(family: 'raw_string_stream', resolver: 'scalar')]
                        public function triggerDefaultResolver() {}
                    };

                    $methodReflector = (new \ReflectionClass($classMock))->getMethod('triggerDefaultResolver');
                    
                    // Call extract_families_from_reflector directly via fluent pipeline chaining rules
                    $families = (new \ReflectionClass(SRW_Logger::class))->
                        getMethod('extract_families_from_reflector')->
                        invoke(null, $methodReflector);
                    
                    expect($families)->not->toBeEmpty()
                        ->and($families[0]['name'])->toBe('raw_string_stream');
                });
            }
        );
    }
);
