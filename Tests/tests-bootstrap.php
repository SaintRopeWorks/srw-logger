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

    // GLOB_BRACE combined with {,.}* finds both regular and hidden files
    $files = glob($dir . '/{,.}*', GLOB_BRACE);

    if ($files !== false) {
        foreach ($files as $file) {
            // Strictly skip current and parent directory pointers
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

// WordPress function stubs for running outside a full WP bootstrap

function wp_upload_dir() {
    return [
        'basedir' => 
            srw_test_log_dir() .
                '/wp-content/uploads'
    ];
}

function sanitize_file_name(string $filename): string {
    return preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $filename);
}

function unit_group(\Closure $fn): void
{
    describe('[Unit Tests]', fn() => $fn());
}

function integration_group(\Closure $fn): void
{
    describe('[Integration]', fn() => $fn());
}