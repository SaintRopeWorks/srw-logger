<?php

declare(
    strict_types =
        1
);

namespace SRW\Logger\Public\Classes;

class SRW_Logger_Admin {

    public static function init() {
        add_action('admin_menu', [self::class, 'add_settings_page']);
        add_action('admin_init', [self::class, 'register_settings_fields']);
        add_action('admin_init', [self::class, 'handle_clear_logs_action']);
        
        // Inject our localized data parameters cleanly to the head section
        add_action('admin_head', [self::class, 'inject_admin_head_meta']);
    }

    public static function add_settings_page() {
        add_options_page(
            'SRW Logger Settings', 
            'SRW Logger', 
            'manage_options', 
            'srw-core-logger', 
            [
                self::class, 
                'render_settings_ui'
            ]
        );
    }

    public static function register_settings_fields() {
        register_setting('srw_logger_group', 'srw_log_retention_days', [
            'type' => 'integer', 'sanitize_callback' => 'intval', 'default' => 14
        ]);
        
        register_setting('srw_logger_group', 'srw_log_format_string', [
            'type' => 'string', 
            'sanitize_callback' => [self::class, 'sanitize_format_template_string'], 
            'default' => '[{DATE:Y-m-d} {TIME:H:i:s.v}] [{LEVEL}] [{CONTEXT}] | {MESSAGE}'
        ]);
    }

    public static function sanitize_format_template_string($input): string {
        return wp_kses($input, []); 
    }

    public static function handle_clear_logs_action() {
        if (!isset($_POST['srw_clear_logs_nonce']) || !wp_verify_nonce($_POST['srw_clear_logs_nonce'], 'srw_clear_logs')) return;
        if (!current_user_can('manage_options')) wp_die('Unauthorized permissions.');

        $wp_upload = wp_upload_dir();
        $log_dir  = $wp_upload['basedir'] . '/srw-enterprise-logs';

        if (file_exists($log_dir)) {
            $files = glob($log_dir . '/*.log');
            if (!empty($files)) { foreach ($files as $file) { if (is_file($file)) @unlink($file); } }
            wp_redirect(admin_url('options-general.php?page=srw-core-logger&logs_cleared=1'));
            exit;
        }
    }

    public static function inject_admin_head_meta() {
        // Safe scalar initialization of server variables into the JavaScript DOM space
        if (isset($_GET['page']) && $_GET['page'] === 'srw-core-logger') {
            echo '<script>var srw_site_name = "' . esc_js(get_bloginfo('name')) . '";</script>';
        }
    }

    public static function render_settings_ui() {
        if (!current_user_can('manage_options')) return;

        $current_retention = intval(get_option('srw_log_retention_days', 14));
        $current_format = get_option('srw_log_format_string', '[{DATE:Y-m-d} {TIME:H:i:s.v}] [{LEVEL}] [{CONTEXT}] | {MESSAGE}');
        
        if (empty($current_format) || !str_contains($current_format, '{')) {
            $current_format = '[{DATE:Y-m-d} {TIME:H:i:s.v}] [{LEVEL}] [{CONTEXT}] | {MESSAGE}';
        }

        $wp_upload = wp_upload_dir();
        $log_dir  = $wp_upload['basedir'] . '/srw-enterprise-logs';
        $file_count = !empty(glob($log_dir . '/*.log')) ? count(glob($log_dir . '/*.log')) : 0;

        if (isset($_GET['logs_cleared'])) {
            echo '<div class="notice notice-success is-dismissible"><p>✅ All log stream files purged successfully.</p></div>';
        }
        ?>
        <style>
            .srw-editor-box {
                border: 1px solid #8c8f94; background: #fff; padding: 12px; min-height: 44px; border-radius: 4px;
                font-family: monospace; font-size: 14px; line-height: 24px; box-shadow: inset 0 1px 2px rgba(0,0,0,.07); outline: none;
            }
            .srw-editor-box:focus { border-color: #2271b1; box-shadow: 0 0 0 1px #2271b1; }
            .srw-token {
                background: #e7f3ff; color: #1d64a7; border: 1px solid #b8daff; padding: 2px 6px; border-radius: 3px;
                font-weight: bold; display: inline-block; margin: 0 2px; user-select: none; cursor: pointer;
            }
            .srw-preview-box {
                background: #f0f0f1; border-left: 4px solid #72aee6; padding: 12px; font-family: monospace; margin-top: 8px; font-size: 13px; color: #2c3338;
            }
            .srw-modal-context {
                display: none; position: fixed; z-index: 100000; background: #fff;
                border: 1px solid #ccc; box-shadow: 0 2px 10px rgba(0,0,0,0.15); padding: 10px; border-radius: 4px;
            }
        </style>

        <div class="wrap">
            <h1>⚙️ SRW Logger Config</h1>
            <p class="description">Construct complex rich-text template lines with live formatting resolution tokens.</p>
            
            <form method="post" id="srw-logger-form" action="options.php" style="margin-top:20px;">
                <?php settings_fields('srw_logger_group'); ?>
                
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="srw_log_retention_days">Log Retention Policy</label></th>
                            <td>
                                <input type="number" id="srw_log_retention_days" name="srw_log_retention_days" value="<?php echo esc_attr($current_retention); ?>" min="1" max="365" class="small-text" />
                                <span class="description">Days to retain active log sheets.</span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label>Advanced Format Builder</label></th>
                            <td>
                                <div style="margin-bottom: 10px;">
                                    <select id="srw-token-selector" style="max-width: 200px;">
                                        <option value="">➕ Insert Variable...</option>
                                        <option value="SITENAME">🌐 WordPress Site Name</option>
                                        <option value="DATE">⏰ Date Token</option>
                                        <option value="TIME">⏳ Time Token</option>
                                        <option value="LEVEL">🏷️ Log Level</option>
                                        <option value="CONTEXT">🛠️ File/Function Context</option>
                                        <option value="MESSAGE">💬 Log Message Text</option>
                                    </select>
                                </div>

                                <div id="srw-rich-editor" class="srw-editor-box" contenteditable="true"></div>
                                <input type="hidden" id="srw_log_format_string" name="srw_log_format_string" value="<?php echo esc_attr($current_format); ?>" />
                                
                                <h3 style="margin-top:15px; margin-bottom:5px;">Live Line Preview</h3>
                                <div id="srw-live-preview" class="srw-preview-box">Loading engine simulation data...</div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php submit_button('Save Configurations Matrix'); ?>
            </form>

            <div id="srw-token-modal" class="srw-modal-context">
                <label style="font-weight:bold; display:block; margin-bottom:5px;">Select Output Format Style:</label>
                <select id="srw-pattern-sub-selector"></select>
                <div style="margin-top:8px; display:flex; gap:5px; justify-content:flex-end;">
                    <button type="button" class="button" id="srw-modal-close">Cancel</button>
                    <button type="button" class="button button-primary" id="srw-modal-save">Apply Format</button>
                </div>
            </div>

            <hr style="margin: 30px 0; border: none; border-top: 1px solid #ccc;" />
            <h2>📊 Vault Diagnostics & Actions</h2>
            <table class="widefat fixed striped" style="max-width: 600px; margin-bottom:20px;">
                <tbody>
                    <tr><td><strong>Secure Log Storage Path:</strong></td><td><code><?php echo esc_html($log_dir); ?></code></td></tr>
                    <tr><td><strong>Active Stream Files Allocated:</strong></td><td><span class="badge" style="background:#0073aa; color:#fff; padding:2px 8px; border-radius:10px; font-weight:bold;"><?php echo esc_html($file_count); ?> files</span></td></tr>
                </tbody>
            </table>

            <form method="post" action="" onsubmit="return confirm('⚠️ Irreversible: Clear all active logs?');">
                <?php wp_nonce_field('srw_clear_logs', 'srw_clear_logs_nonce'); ?>
                <input type="submit" class="button button-link-delete" style="color:#d63638; border:1px solid #d63638;" value="🗑️ Clear All Active Log Files Now" />
            </form>
        </div>
        
        <?php
        // Dynamically pull the pure client asset script down from a separate file container
        require_once dirname(__DIR__, 2) . '/Private/Assets/admin-wizard.js';
    }
}
