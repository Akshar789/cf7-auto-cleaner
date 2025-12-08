<?php
/**
 * Logs page admin interface.
 *
 * @package CF7_Auto_Cleaner
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Logs page class.
 */
class CF7AC_Logs_Page
{

    /**
     * Render logs page.
     */
    public static function render()
    {
        // Check permissions.
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'cf7-auto-cleaner'));
        }

        // Handle actions.
        if (isset($_POST['cf7ac_export_logs'])) {
            self::export_logs();
        }

        if (isset($_POST['cf7ac_clear_logs'])) {
            self::clear_logs();
        }

        if (isset($_POST['cf7ac_delete_log']) && isset($_POST['log_id'])) {
            self::delete_log();
        }

        if (isset($_POST['cf7ac_update_log']) && isset($_POST['log_id'])) {
            self::update_log();
        }

        // Get filter parameters.
        $current_page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
        $per_page = 20;

        // Handle quick filters
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
        
        if (isset($_GET['quick_filter'])) {
            $quick_filter = sanitize_key($_GET['quick_filter']);
            $today = current_time('Y-m-d');
            
            switch ($quick_filter) {
                case 'today':
                    $date_from = $today;
                    $date_to = $today;
                    break;
                case 'last7':
                    $date_from = date('Y-m-d', strtotime('-7 days', current_time('timestamp')));
                    $date_to = $today;
                    break;
                case 'last30':
                    $date_from = date('Y-m-d', strtotime('-30 days', current_time('timestamp')));
                    $date_to = $today;
                    break;
            }
        }

        $args = array(
            'page' => $current_page,
            'per_page' => $per_page,
            'form_id' => isset($_GET['form_id']) ? absint($_GET['form_id']) : 0,
            'action_taken' => isset($_GET['action_taken']) ? sanitize_key($_GET['action_taken']) : '',
            'date_from' => $date_from,
            'date_to' => $date_to,
            'search' => isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '',
            'resolved' => isset($_GET['resolved']) ? sanitize_text_field($_GET['resolved']) : '',
        );

        // Get logs.
        $logs = CF7AC_Database::get_logs($args);
        $total_logs = CF7AC_Database::get_logs_count($args);
        $total_pages = ceil($total_logs / $per_page);

        // Get all CF7 forms for filter.
        $cf7_forms = get_posts(
            array(
                'post_type' => 'wpcf7_contact_form',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC',
            )
        );

        // Render page.
        include CF7AC_PLUGIN_DIR . 'admin/views/logs-page.php';
    }

    /**
     * Export logs to CSV.
     */
    private static function export_logs()
    {
        // Verify nonce.
        if (!isset($_POST['cf7ac_export_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cf7ac_export_nonce'])), 'cf7ac_export_logs')) {
            wp_die(esc_html__('Security check failed.', 'cf7-auto-cleaner'));
        }

        // Get filter args.
        $args = array(
            'form_id' => isset($_POST['form_id']) ? absint($_POST['form_id']) : 0,
            'action_taken' => isset($_POST['action_taken']) ? sanitize_key($_POST['action_taken']) : '',
            'date_from' => isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '',
            'date_to' => isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '',
        );

        // Generate CSV.
        $csv = CF7AC_Logger::export_to_csv($args);

        // Send headers.
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=cf7ac-logs-' . gmdate('Y-m-d') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $csv;
        exit;
    }

    /**
     * Clear all logs.
     */
    private static function clear_logs()
    {
        // Verify nonce.
        if (!isset($_POST['cf7ac_clear_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cf7ac_clear_nonce'])), 'cf7ac_clear_logs')) {
            wp_die(esc_html__('Security check failed.', 'cf7-auto-cleaner'));
        }

        CF7AC_Database::delete_all_logs();

        add_settings_error(
            'cf7ac_messages',
            'cf7ac_message',
            __('All logs cleared successfully.', 'cf7-auto-cleaner'),
            'success'
        );
    }

    /**
     * Delete a single log entry.
     */
    private static function delete_log()
    {
        // Verify nonce.
        if (!isset($_POST['cf7ac_delete_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cf7ac_delete_nonce'])), 'cf7ac_delete_log')) {
            wp_die(esc_html__('Security check failed.', 'cf7-auto-cleaner'));
        }

        $log_id = absint($_POST['log_id']);
        CF7AC_Database::delete_log($log_id);

        add_settings_error(
            'cf7ac_messages',
            'cf7ac_message',
            __('Log entry deleted successfully.', 'cf7-auto-cleaner'),
            'success'
        );
    }

    /**
     * Update a log entry.
     */
    private static function update_log()
    {
        // Verify nonce.
        if (!isset($_POST['cf7ac_update_log_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cf7ac_update_log_nonce'])), 'cf7ac_update_log')) {
            wp_die(esc_html__('Security check failed.', 'cf7-auto-cleaner'));
        }

        $log_id = absint($_POST['log_id']);
        $resolved = isset($_POST['resolved_flag']) ? 1 : 0;
        $admin_note = isset($_POST['admin_note']) ? sanitize_textarea_field($_POST['admin_note']) : '';

        CF7AC_Database::update_log(
            $log_id,
            array(
                'resolved_flag' => $resolved,
                'admin_note' => $admin_note,
            )
        );

        add_settings_error(
            'cf7ac_messages',
            'cf7ac_message',
            __('Log updated successfully.', 'cf7-auto-cleaner'),
            'success'
        );
    }
}
