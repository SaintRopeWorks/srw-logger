<?php
// tests-bootstrap.php
function wp_upload_dir() {
    return ['basedir' => sys_get_temp_dir() . '/srw_test_logs'];
}
function sanitize_file_name($filename) {
    return $filename;
}

require_once __DIR__ . '/vendor/autoload.php';

/**
 * Native TextDox Symmetrical Prefix Hook
 * Intercepts PHPUnit's execution stream and stamps custom suite prefixes dynamically.
 */
spl_autoload_register(function (string $class_name) {
    if (str_contains($class_name, 'Tests\\Unit\\')) {
        $short_name = basename(str_replace('\\', '/', $class_name));
        if (!class_exists("[Unit] {$short_name}", false)) {
            class_alias($class_name, "[Unit] {$short_name}");
        }
    } elseif (str_contains($class_name, 'Tests\\Integration\\')) {
        $short_name = basename(str_replace('\\', '/', $class_name));
        if (!class_exists("[Integration] {$short_name}", false)) {
            class_alias($class_name, "[Integration] {$short_name}");
        }
    }
}, true, true);