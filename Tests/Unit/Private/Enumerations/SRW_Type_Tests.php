<?php
declare(strict_types=1);

namespace Tests\Unit\Private\Enumerations;

use SRW\Logger\Private\Enumerations\SRW_Log_Type;

unit_group(
    function () {
        it('Confirms accurate classification mapping values are matched', function () {
            expect(SRW_Log_Type::Data->value)->toBe('DATA');
        });
    }
);
