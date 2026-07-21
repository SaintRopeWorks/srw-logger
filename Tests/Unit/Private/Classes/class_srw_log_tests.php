<?php declare(strict_types=1);

namespace Tests\Unit\Private\Classes;

use Tests\SRWTestCaseBase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use SRW\Logger\Private\Classes\SRW_Log;
use SRW\Logger\Private\Enumerations\SRW_Log_Level;

#[TestDox('SRW_Log Class (Pure Unit Isolation)')]
class class_srw_log_tests extends SRWTestCaseBase {

    #[Test]
    #[TestDox('Verifies the constructor cleans invalid filename characters and populates attributes')]
    public function test_constructor_sanitizes_log_name_and_initializes_properties(): void {
        $log_worker = new SRW_Log(
            name: 'User<Profile>Input*"Matrix"', 
            directory: $this->test_log_dir, 
            is_family: true, 
            append: true
        );

        $this->assertTrue($log_worker->is_family());
        $this->assertEquals('User_Profile_Input*_Matrix_', $log_worker->get_name());
    }

    #[Test]
    #[TestDox('Verifies the direct write loop appends clean lines to the filesystem target')]
    public function test_direct_write_appends_clean_formatted_message_payload(): void {
        $log_worker = new SRW_Log(
            name: 'direct_unit_stream', 
            directory: $this->test_log_dir, 
            is_family: false, 
            append: false
        );

        $log_worker->write(
            message: 'Direct unit test bypass verification payload.',
            data: ['debug_key' => 'verified_unit'],
            level: SRW_Log_Level::Warn,
            component_context: 'PureUnitTestContext'
        );

        $expected_file = $this->test_log_dir . '/direct_unit_stream.log';
        $this->assertFileExists($expected_file);

        $contents = file_get_contents($expected_file);
        $this->assertStringContainsString('[WARN]', $contents);
        $this->assertStringContainsString('SRW_Log::new_log_line()', $contents);
        $this->assertStringContainsString('verified_unit', $contents);
    }
}
