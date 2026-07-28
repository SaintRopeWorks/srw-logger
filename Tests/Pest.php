<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

uses(TestCase::class)
    ->beforeEach(function () {
        if (!file_exists(srw_test_log_dir())) {
            mkdir(srw_test_log_dir(), 0755, true);
        }
    })
    ->afterEach(function () {
        if (file_exists(srw_test_log_dir())) {
            srw_delete_directory(srw_test_log_dir());
        }

        unset($GLOBALS['srw_mock_caller_context']);
        unset($GLOBALS['test_active_pipeline']);
    })
    ->in('Unit', 'Integration');