<?php
declare(strict_types=1);

namespace Tests\Integration\Public\Attributes;

use SRW\Logger\Public\Attributes\LogFamily;

integration_group(
    function() {
        it('Validates attribute property bindings retain parameter integrity during construction', function () {
            $attribute = new LogFamily(family: 'orders_channel', path: 'billing/v1', resolver: 'option');
            
            expect($attribute->family)->toBe('orders_channel')
                ->and($attribute->path)->toBe('billing/v1')
                ->and($attribute->resolver)->toBe('option');
        });
    }
);
