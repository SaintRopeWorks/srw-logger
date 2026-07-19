<?php
// tests-bootstrap.php
function wp_upload_dir() {
    return ['basedir' => sys_get_temp_dir() . '/srw_test_logs'];
}
function sanitize_file_name($filename) {
    return $filename;
}

require_once __DIR__ . '/vendor/autoload.php';
