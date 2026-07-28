<?php
declare(strict_types=1);

namespace SRW\Logger;

use SRW\Logger\Public\Classes\SRW_Logger;
use SRW\Logger\Public\Classes\SRW_Logger_Admin;

if (defined('ABSPATH')) {
    
    // Wire up runtime filesystem pathways once core configurations are compiled
    add_action('plugins_loaded', function() {
        $upload_dir = wp_upload_dir();
        $target_path = ($upload_dir && isset($upload_dir['basedir'])) 
            ? $upload_dir['basedir'] . '/srw-enterprise-logs' 
            : sys_get_temp_dir() . '/srw-enterprise-logs';

        SRW_Logger::setup($target_path);
    });

    // Wire up the Excel-style administration configuration page wizard
    add_action('admin_init', function() {
        SRW_Logger_Admin::init();
    });

} else {
    // NATIVE PHP CONTEXT: Fallback for isolated CLI test suites or vanilla script threads
    SRW_Logger::setup(sys_get_temp_dir() . '/srw-native-logs');
}
