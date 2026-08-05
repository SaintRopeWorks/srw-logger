<?php
declare(strict_types=1);

namespace Tests\Integration\Private\Enumerations;

use SRW\Logger\Private\Enumerations\SRW_Log_Level;

integration_group(
    function() {
        it('Validates all defined log levels have strict expected string mappings', function (SRW_Log_Level $level, string $expectedString) {
            expect($level->value)->toBe($expectedString);
        })->with([
            [SRW_Log_Level::Info, 'INFO'],
            [SRW_Log_Level::Warn, 'WARN'],
            [SRW_Log_Level::Error, 'ERROR'],
            [SRW_Log_Level::Verbose, 'VERBOSE'],
        ]);
    }
);
