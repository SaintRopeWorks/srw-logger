<?php
declare(strict_types=1);

namespace Tests\Unit\Public\Classes;

use SRW\Logger\Public\Classes\SRW_Logger_Admin;

unit_group(
    function() {
        it('Verifies template normalization handles completely unbracketed string definitions safely', function () {
            if (!function_exists('wp_kses')) { function wp_kses($s, $a) { return $s; } }
            
            $input = "RAW_LOGGING_LINE_WITHOUT_TOKENS";
            $output = SRW_Logger_Admin::sanitize_format_template_string($input);
            expect($output)->toBe("RAW_LOGGING_LINE_WITHOUT_TOKENS");
        });
    }
);
