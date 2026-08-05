<?php
declare(strict_types=1);

// Force core WordPress function stubs into the root global namespace
// Re-open your targeted testing namespace for Pest integration loop tracks
namespace Tests\Integration\Public\Classes;

    use SRW\Logger\Public\Classes\SRW_Logger_Admin;

    integration_group(
        function() {
            beforeEach(function() {
                $GLOBALS['wp_mock_actions'] = [];
                $GLOBALS['wp_redirect_url'] = null;
                $GLOBALS['wp_current_user_can'] = true;
                $_POST = [];
            });

            it('Registers settings and action hooks during initialization', function () {
                SRW_Logger_Admin::init();
                expect($GLOBALS['wp_mock_actions'])->toHaveKeys(['admin_menu', 'admin_init', 'admin_head']);
            });

            it('Strips HTML and executable tags from formatting templates', function () {
                $dirtyString = "<script>alert('xss')</script>[{LEVEL}] | {MESSAGE}";
                $cleanString = SRW_Logger_Admin::sanitize_format_template_string($dirtyString);
                expect($cleanString)->not->toContain('<script>')->and($cleanString)->toContain('{MESSAGE}');
            });

            it('Rejects file clear actions if security nonces are invalid or missing', function () {
                $_POST['srw_clear_logs_nonce'] = 'invalid_token';
                SRW_Logger_Admin::handle_clear_logs_action();
                expect($GLOBALS['wp_redirect_url'])->toBeNull();
            });

            it('Halts processing if a non-admin attempts to purge storage folders', function () {
                $_POST['srw_clear_logs_nonce'] = 'valid_token';
                $GLOBALS['wp_current_user_can'] = false;
                
                expect(fn() => SRW_Logger_Admin::handle_clear_logs_action())->toThrow(\Exception::class, 'WP_DIE: Unauthorized permissions.');
            });
        }
    );
