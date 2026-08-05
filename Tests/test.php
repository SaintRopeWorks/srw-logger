<?php

require "vendor/autoload.php"; 

use SRW\Logger\Public\Classes\SRW_Logger; 

SRW_Logger::setup(null); 

array_map(
    fn() => 
        SRW_Logger::info("MANUAL_CLI_TEST"),
    [1,2,3]
);
    
echo "Execution finished safely\n";