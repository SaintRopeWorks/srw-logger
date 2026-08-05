<?php 

declare(
    strict_types =
        1
);

namespace Tests\Integration\Private\Classes;

use SRW\Logger\Public\Classes\SRW_Logger;
use SRW\Logger\Public\Attributes\LogFamily;
use ReflectionMethod;
use ReflectionClass;

integration_group(
    function(
    ){
        describe(
            "Verifies engine line compiler fallback branches evaluate cleanly",
            function () {
                beforeEach(function () {
                    // FORCE STATIC CACHE REGISTER RESET VIA REFLECTION
                    $reflector = new \ReflectionClass(\SRW\Logger\Public\Classes\SRW_Logger::class);
                    $property = $reflector->getProperty('_call_stack');
                    $property->setValue(null, null);

                    SRW_Logger::clear_calls();
                    $_POST = [];
                });

                afterEach(function () {
                    // Always re-enable the stub to protect other integration tests
                    $GLOBALS['srw_mock_cli_mode'] = false;
                });

                it("Uses hardcoded formatting defaults when the call stack lacks tracking properties", function () {
                    // WAKE UP GAPS: Force the get_bloginfo stub to step aside
                    $GLOBALS['srw_mock_cli_mode'] = true;

                    // 1. Break the stack framing context properties intentionally
                    $GLOBALS['srw_mock_caller_context'] = [
                        'target_name' => 'fallback_branch_stream',
                        'basename' => null,
                        'line' => null,
                        'resolved_function' => null,
                        'families' => []
                    ];
                    
                    // 2. GAP 2 RESOLUTION: Use a layout layout string containing BOTH time structures at once
                    SRW_Logger::setup(srw_test_log_dir());
                    SRW_Logger::set_format_string(
                        '[{SITENAME}->{CONTEXT}][{DATE:Y}] [{TIME:H:i:s.v}] [{TIME:H:i:s}] | {MESSAGE}'
                    );
                    
                    $logInstance = new \SRW\Logger\Private\Classes\SRW_Log(
                        'fallback_branch_stream', 
                        srw_test_log_dir(), 
                        false, 
                        true
                        );
                    $logInstance->write(
                        'Testing fallback triggers', 
                        null, 
                        \SRW\Logger\Private\Enumerations\SRW_Log_Level::Info, 
                        'CustomComponentContext'
                    );
                    
                    $content = file_get_contents(srw_test_log_dir() . '/fallback_branch_stream.log');
                    
                    // Verify that empty function definitions fall back to standard placeholders safely
                    expect($content)->toContain(' -> CustomComponentContext()')
                        ->and($content)->toContain('index.php:0');
                });
            }
        );
        describe(
            "Verifies dynamic attribute resolution mapping using the 'global' resolver pattern",
            function(
            ){
                beforeEach(
                    function(
                    ) {
                        SRW_Logger::clear_calls();
                        SRW_Logger::setup(
                            srw_test_log_dir()
                        );
                        $GLOBALS['test_active_pipeline'] = 'Integration_Suite_Family';
                        $discovered_families = 
                            new ReflectionClass(
                                SRW_Logger::class
                            )->
                                getMethod(
                                    'extract_families_from_reflector'
                                )->
                                    invoke(
                                        null, 
                                        new ReflectionMethod(
                                           SRW_Test_Mock_Runner::class, 
                                            'execute_mock_lambda'
                                        )
                                    );
                        $GLOBALS['srw_mock_caller_context'] = [
                            'target_name' => 'SRW_Test_Mock_Runner_execute_mock_lambda',
                            'basename' => 'class_srw_log_tests.php',
                            'line' => 150,
                            'resolved_function' => 'SRW_Test_Mock_Runner::execute_mock_lambda()',
                            'families' => $discovered_families
                        ];

                        SRW_Logger::warn("Triggering pipeline alert.");

                        $this->
                            expected_family_file = 
                                srw_test_log_dir() . 
                                    '/Integration_Suite_Family.log';
                        $this->
                            contents = 
                                file_get_contents(
                                    $this->
                                        expected_family_file
                                );
                    }
                );
                it(
                    "Created the Family Log File",
                    function(
                    ) {
                        expect(
                            $this->
                                expected_family_file
                        )->
                            toBeFile(
                            );
                    }
                );
                it(
                    "Wrote to the Family Log",
                    fn(
                        string $searchString
                    ) => 
                        expect(
                            $this->
                                contents
                        )->
                            toContain(
                                $searchString
                            )
                )->
                    with(
                        [
                            '[WARN]',
                            'SRW_Test_Mock_Runner::execute_mock_lambda()', 
                            'Triggering pipeline alert.'
                        ]
                    );
            }
        );
        describe(
            "Engine climbs past recursive closures and loops",
            function(
            ){
                beforeEach(
                    function(
                    ){
                        SRW_Logger::setup(
                            srw_test_log_dir()
                        );
                        $recursive_runner = new SRW_Test_Recursive_Runner();
                        $recursive_runner->trigger_recursive_loop(3);

                        $this->
                            expected_file = 
                                srw_test_log_dir() . 
                                    '/SRW_Test_Recursive_Runner_trigger_recursive_loop.log';
                        $this->
                            contents = 
                                file_get_contents(
                                    $this->
                                        expected_file
                                );
                    }
                );
                it(
                    "Created the Log File",
                    function(
                    ) {
                        expect(
                            $this->
                                expected_file
                        )->
                            toBeFile(
                            );
                    }
                );
                it(
                    "Wrote to the Log",
                    fn(
                        string $searchString
                    ) => 
                        expect(
                            $this->
                                contents
                        )->
                            toContain(
                                $searchString
                            )
                )->
                    with(
                        [
                            'SRW_Test_Recursive_Runner::trigger_recursive_loop()',
                            'Processing recursive depth level: 3'
                        ]
                    );
            }
        );
        describe(
            "Verifies relative sub-path parameters map safely",
            function(
            ){
                beforeEach(
                    function(
                    ) {
                        SRW_Logger::setup(
                            srw_test_log_dir()
                        );
                        $discovered_families = [['name' => 'Integration_Suite_Family', 'path' => 'nested/sub/folder']];
                        $GLOBALS['srw_mock_caller_context'] = [
                            'target_name' => 'SRW_Test_Mock_Runner_execute_mock_lambda',
                            'basename' => 'class_srw_log_tests.php',
                            'line' => 210,
                            'resolved_function' => 'SRW_Test_Mock_Runner::execute_mock_lambda()',
                            'families' => $discovered_families
                        ];

                        SRW_Logger::info("Testing nested subdirectory allocation.");

                        $this->
                            expected_nested_dir =
                                srw_test_log_dir() . 
                                    '/nested/sub/folder';
                        $this->
                            expected_file = 
                                $this->
                                    expected_nested_dir . 
                                        '/Integration_Suite_Family.log';
                    }
                );
                it(
                    "Created nested directory",
                    fn(
                    ) =>
                        expect(
                            $this->
                                expected_nested_dir
                        )->
                            toBeDirectory(
                            )
                );
                it(
                    "Created file",
                    fn(
                    ) =>
                        expect(
                            $this->
                                expected_file
                        )->
                            toBeFile(
                            )
                );
            }
        );
        describe(
            "Verifies downstream cascading for family logger instances",
            function () {
                beforeEach(function () {
                    SRW_Logger::clear_calls();
                    SRW_Logger::setup(srw_test_log_dir());
                    
                    // Register dynamic nested context mapping rules
                    $GLOBALS['srw_mock_caller_context'] = [
                        'target_name' => 'SRW_Test_Cascade_Runner',
                        'basename' => 'class_srw_log_tests.php',
                        'line' => 290,
                        'resolved_function' => 'SRW_Test_Cascade_Runner()',
                        'families' => [
                            ['name' => 'PrimaryFamily', 'path' => null],
                            ['name' => 'SecondaryFamily', 'path' => null]
                        ]
                    ];
                });
                it("Cascades a single message across multiple registered family logs", function () {
                    SRW_Logger::info("Broadcasting message stream to all families.");
                    
                    $primaryFile = srw_test_log_dir() . '/PrimaryFamily.log';
                    $secondaryFile = srw_test_log_dir() . '/SecondaryFamily.log';
                    
                    expect($primaryFile)->toBeFile();
                    expect($secondaryFile)->toBeFile();
                    expect(file_get_contents($primaryFile))->toContain("Broadcasting message stream");
                    expect(file_get_contents($secondaryFile))->toContain("Broadcasting message stream");
                });
            }
        );

        describe(
            "Verifies raw data structures and json serialization limits",
            function () {
                beforeEach(function () {
                    SRW_Logger::setup(srw_test_log_dir());
                    $this->log_worker = new \SRW\Logger\Private\Classes\SRW_Log('data-fault-stream', srw_test_log_dir(), false, true);
                });
                it("Gracefully handles non-serializable resource items", function () {
                    $resource = fopen('php://memory', 'r');
                    $this->log_worker->write("Testing resource serialization", $resource, \SRW\Logger\Private\Enumerations\SRW_Log_Level::Error);
                    fclose($resource);
                    
                    $content = file_get_contents(srw_test_log_dir() . '/data-fault-stream.log');
                    
                    expect($content)->toContain('[ERROR]')
                        ->and($content)->toContain('__serialization_error')
                        ->and($content)->toContain('resource');
                });
            }
        );

    }
);

/**
 * Isolated Symmetrical Test Mocks & Tracing Loops
 */
class SRW_Test_Mock_Runner {
    #[LogFamily(family: 'test_active_pipeline', resolver: 'global')]
    #[LogFamily(family: 'test_child_folder', path: 'subfolder')]
    public function execute_mock_lambda(): void {
        SRW_Logger::warn("Triggering pipeline alert.");
    }
}

class SRW_Test_Recursive_Runner {
    public function trigger_recursive_loop(int $depth): void {
        if ($depth <= 0) return;
        $array_mock = [0];
        foreach ($array_mock as $item){
            SRW_Logger::info(
                "Processing recursive depth level: " . 
                    $depth
            );
            $this->trigger_recursive_loop($depth - 1);
        }
    }
}
