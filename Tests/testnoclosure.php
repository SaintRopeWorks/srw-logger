<?php

require "vendor/autoload.php"; 

use SRW\Logger\Public\Classes\SRW_Logger; 

SRW_Logger::setup(null); 

SRW_Logger::info("MANUAL_CLI_TEST");
    
echo "Execution finished safely\n";