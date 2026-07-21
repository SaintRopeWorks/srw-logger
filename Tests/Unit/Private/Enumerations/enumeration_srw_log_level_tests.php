<?php
declare(
    strict_types =
        1
);

namespace Tests\Unit\Private\Enumerations;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

#[
    TestDox(
        "SRW Log Level Enumeration (Pure Unit Isolation)"
    )
]
class enumeration_srw_log_level_tests extends TestCase {
    #[
        Test
    ]
    #[
        TestDox(
            "For Future Use"
        )
    ]
    public function it_exists_for_future_enum_tests(        
    ): void {
        $this->
            assertTrue(
                true, 
                'I exist in case I am needed in the future for SRW_Log_Level.'
            );
    }
}