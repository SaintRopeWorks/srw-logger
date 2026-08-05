<?php
declare(strict_types=1);

namespace Tests\Integration\Private\Enumerations;

use SRW\Logger\Private\Enumerations\SRW_Log_Type;

integration_group(
    function() {
        it('Validates structural log type mapping rules match core design patterns', function (SRW_Log_Type $type, string $expectedKey) {
            expect($type->value)->toBe($expectedKey);
        })->with([
            [SRW_Log_Type::Message, 'MSG'],
            [SRW_Log_Type::Data, 'DATA'],
        ]);
    }
);
