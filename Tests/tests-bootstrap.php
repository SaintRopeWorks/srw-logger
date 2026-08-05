<?php
// tests-bootstrap.php

require_once __DIR__ . '/vendor/autoload.php';

function srw_test_log_dir(): string
{
    return sys_get_temp_dir() . '/srw_test_logs';
}

function srw_delete_directory(string $dir): void {
    if (!is_dir($dir)) {
        return;
    }

    $files = glob($dir . '/{,.}*', GLOB_BRACE);

    if ($files !== false) {
        foreach ($files as $file) {
            if (basename($file) === '.' || basename($file) === '..') {
                continue;
            }

            if (is_dir($file)) {
                srw_delete_directory($file);
            } else {
                @unlink($file);
            }
        }
    }

    @rmdir($dir);
}

function named_dataset(array $values, callable $labeler): array
{
    return array_combine(
        array_map($labeler, $values),
        $values
    );
}

function unit_group(\Closure $fn): void
{
    describe('[Unit Tests]', fn() => $fn());
}

function integration_group(\Closure $fn): void
{
    describe('[Integration]', fn() => $fn());
}

// =========================================================================
// UNIVERSAL WORDPRESS CORE EMULATION LAYER
// =========================================================================

if (!function_exists('wp_upload_dir')) {
    function wp_upload_dir() {
        return [
            'basedir' => srw_test_log_dir() . '/wp-content/uploads'
        ];
    }
}

if (!function_exists('sanitize_file_name')) {
    function sanitize_file_name(string $filename): string {
        return preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $filename);
    }
}

if (!function_exists('get_option')) {
    function get_option($key, $default = false) {
        return $GLOBALS['wp_mock_options'][$key] ?? $default;
    }
}

if (!function_exists('add_action')) {
    function add_action($tag, $callback) { 
        $GLOBALS['wp_mock_actions'][$tag][] = $callback; 
    }
}

if (!function_exists('register_setting')) {
    function register_setting($g, $o, $a = []) {}
}

if (!function_exists('add_options_page')) {
    function add_options_page($t, $m, $c, $s, $cb) {}
}

if (!function_exists('wp_kses')) {
    function wp_kses($string, $allowed_html, $allowed_protocols = []) { 
        return strip_tags($string); 
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($cap) { 
        return $GLOBALS['wp_current_user_can'] ?? false; 
    }
}

if (!function_exists('wp_die')) {
    function wp_die($msg) { 
        throw new \Exception("WP_DIE: " . $msg); 
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action) { 
        return $nonce === 'valid_token'; 
    }
}

if (!function_exists('admin_url')) {
    function admin_url($path) { 
        return '/wp-admin/' . $path; 
    }
}

if (!function_exists('wp_redirect')) {
    function wp_redirect($location) { 
        $GLOBALS['wp_redirect_url'] = $location; 
    }
}

if (!function_exists('get_bloginfo')) {
    function get_bloginfo($show = '') {
        return 'WordPress';
    }
}

if (!function_exists('submit_button')) {
    function submit_button($text = null, $type = 'primary', $name = 'submit', $wrap = true, $other_attributes = null) {}
}

if (!function_exists('settings_fields')) {
    function settings_fields($option_group) {}
}

if (!function_exists('esc_attr')) {
    function esc_attr($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
}

if (!function_exists('esc_html')) {
    function esc_html($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
}

if (!function_exists('wp_nonce_field')) {
    function wp_nonce_field($action = -1, $name = "_wpnonce", $referer = true, $display = true) {}
}
