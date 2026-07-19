<?php
declare(
    strict_types =
        1
);

namespace Tests\Public\Attributes;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

#[
    TestDox(
        'SRW_Log_Family Attribute'
    )
]
class attribute_log_family_tests extends TestCase {
    #[
        Test
    ]
    #[
        TestDox(
            "For Future Use"
        )
    ]
    public function it_exists_for_future_attribute_tests(
    ): void {
        $this->
            assertTrue(
                true, 
                'I exist in case I am needed in the future for LogFamily.'
            );
    }
}