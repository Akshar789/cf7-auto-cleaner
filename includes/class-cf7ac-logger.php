<?php
/**
 * Logger class for managing submission logs.
 *
 * @package CF7_Auto_Cleaner
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Logger class.
 */
class CF7AC_Logger
{

    /**
     * Log a submission.
     *
     * @param array $data Log data.
     * @return int|false Log ID or false on failure.
     */
    public static function log($data)
    {
        return CF7AC_Database::insert_log($data);
    }

    /**
     * Cleanup old logs based on retention settings.
     */
    public static function cleanup_old_logs()
    {
        $core = CF7AC_Core::get_instance();
        $retention_days = absint($core->get_setting('log_retention_days', 30));
        $max_logs = absint($core->get_setting('max_logs', 10000));

        // Delete logs older than retention period.
        if ($retention_days > 0) {
            CF7AC_Database::delete_old_logs($retention_days);
        }

        // Enforce max logs limit.
        self::enforce_max_logs($max_logs);
    }

    /**
     * Enforce maximum number of logs.
     *
     * @param int $max_logs Maximum number of logs to keep.
     */
    private static function enforce_max_logs($max_logs)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'cf7ac_logs';

        // Count total logs.
        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");

        if ($total > $max_logs) {
            // Delete oldest logs to stay under limit.
            $to_delete = $total - $max_logs;

            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$table_name} ORDER BY time ASC LIMIT %d",
                    $to_delete
                )
            );
        }
    }

    /**
     * Export logs to CSV.
     *
     * @param array $args Query arguments.
     * @return string CSV content.
     */
    public static function export_to_csv($args = array())
    {
        // Get logs without pagination.
        $args['per_page'] = 999999;
        $logs = CF7AC_Database::get_logs($args);

        // Build CSV.
        $csv = array();

        // Header row.
        $csv[] = array(
            'ID',
            'Time',
            'Form ID',
            'IP',
            'User Agent',
            'Blocked Fields',
            'Action Taken',
            'Excerpt',
            'Resolved',
            'Admin Note',
        );

        // Data rows.
        foreach ($logs as $log) {
            $csv[] = array(
                $log['id'],
                $log['time'],
                $log['form_id'],
                self::maybe_redact_ip($log['ip']),
                $log['user_agent'],
                $log['blocked_fields'],
                $log['action_taken'],
                $log['raw_posted_excerpt'],
                $log['resolved_flag'] ? 'Yes' : 'No',
                $log['admin_note'],
            );
        }

        // Convert to CSV string.
        return self::array_to_csv($csv);
    }

    /**
     * Convert array to CSV string.
     *
     * @param array $data Data array.
     * @return string CSV string.
     */
    private static function array_to_csv($data)
    {
        $output = fopen('php://temp', 'r+');

        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Maybe redact IP address for privacy.
     *
     * @param string $ip IP address.
     * @return string Redacted or original IP.
     */
    private static function maybe_redact_ip($ip)
    {
        // Check if IP redaction is enabled (could be a setting).
        // For now, we'll partially redact IPv4 addresses.
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            if (count($parts) === 4) {
                return $parts[0] . '.' . $parts[1] . '.xxx.xxx';
            }
        }

        return $ip;
    }

    /**
     * Send daily/weekly admin summary email.
     */
    public static function send_admin_summary()
    {
        $core = CF7AC_Core::get_instance();

        // Check if summaries are enabled (would be a setting).
        $admin_email = $core->get_setting('admin_notification_email', '');
        if (empty($admin_email)) {
            return;
        }

        // Get logs from last 24 hours.
        $args = array(
            'date_from' => gmdate('Y-m-d H:i:s', strtotime('-24 hours')),
            'per_page' => 999999,
        );

        $logs = CF7AC_Database::get_logs($args);

        if (empty($logs)) {
            return;
        }

        // Build summary.
        $total_blocked = count($logs);
        $by_action = array();

        foreach ($logs as $log) {
            $action = $log['action_taken'];
            if (!isset($by_action[$action])) {
                $by_action[$action] = 0;
            }
            $by_action[$action]++;
        }

        // Build email.
        $subject = __('[CF7 Auto Cleaner] Daily Summary', 'cf7-auto-cleaner');

        $message = sprintf(
            /* translators: %d: Number of submissions */
            __("CF7 Auto Cleaner processed %d submissions in the last 24 hours.\n\n", 'cf7-auto-cleaner'),
            $total_blocked
        );

        $message .= __("Breakdown by action:\n", 'cf7-auto-cleaner');
        foreach ($by_action as $action => $count) {
            $message .= sprintf("- %s: %d\n", ucfirst($action), $count);
        }

        wp_mail($admin_email, $subject, $message);
    }
}
