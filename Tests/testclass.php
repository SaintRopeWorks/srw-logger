<?php

require "vendor/autoload.php"; 

use SRW\Logger\Public\Classes\SRW_Logger; 

SRW_Logger::setup(null); 

class Test { 
    function doit() {  
        array_map(
            fn() => 
                print_r(SRW_Logger::Information("MANUAL_CLI_TEST")),
            [1,2,3]
        );
    } 
} 

new Test()->
    doit();
    
echo "Execution finished safely\n";