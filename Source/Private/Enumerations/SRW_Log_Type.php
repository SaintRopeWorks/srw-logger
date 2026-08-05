<?php

declare(
    strict_types =
        1
);

namespace SRW\Logger\Private\Enumerations;

enum SRW_Log_Type {
    case Message;
    case Data;
}
