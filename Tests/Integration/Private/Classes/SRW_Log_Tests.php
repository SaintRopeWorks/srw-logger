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
    }
);

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
        foreach ($array_mock as $item){
            SRW_Logger::info(
                "Processing recursive depth level: " . 
                    $depth
            );
            $this->trigger_recursive_loop($depth - 1);
        }
    }
}
