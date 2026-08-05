<?php
declare(strict_types=1);

namespace Tests\Unit\Public\Attributes;

use SRW\Logger\Public\Attributes\LogFamily;

unit_group(
    function () {
        it('Confirms default configuration routing parameters apply correctly', function () {
            $attr = new LogFamily('default_family');
            expect($attr->resolver)->toBe('scalar')->and($attr->path)->toBeNull();
        });
    }
);
