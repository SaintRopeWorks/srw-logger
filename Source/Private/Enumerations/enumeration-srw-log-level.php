<?php

declare(
    strict_types =
        1
);

namespace SRW\Logger\Private\Enumerations;

enum SRW_Log_Level: string {
    case Info    
        = 'INFO';
    case Warn    
        = 'WARN';
    case Error   
        = 'ERROR';
    case Verbose 
        = 'VERBOSE';
}
