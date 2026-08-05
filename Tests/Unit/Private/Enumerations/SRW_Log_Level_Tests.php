<?php
declare(strict_types=1);

namespace Tests\Unit\Private\Enumerations;

use SRW\Logger\Private\Enumerations\SRW_Log_Level;

unit_group(
    function () {
        it('Confirms expected string constants exist inside log levels', function () {
            expect(SRW_Log_Level::Error->value)->toBe('ERROR');
        });
    }
);
