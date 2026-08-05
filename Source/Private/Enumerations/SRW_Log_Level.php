<?php

declare(
    strict_types =
        1
);

namespace SRW\Logger\Private\Enumerations;

enum SRW_Log_Level: int {
    case Verbose = 0;
    case Information = 1;
    case Warning = 2;
    case Error   = 3;
}
