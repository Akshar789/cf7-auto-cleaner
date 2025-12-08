<?php
/**
 * Database operations and schema management.
 *
 * @package CF7_Auto_Cleaner
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Database class for managing logs table.
 */
class CF7AC_Database
{

    /**
     * Table name (without prefix).
     */
    const TABLE_NAME = 'cf7ac_logs';

    /**
     * Create database tables.
     */
    public static function create_tables()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			time datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			form_id bigint(20) UNSIGNED NOT NULL,
			ip varchar(45) DEFAULT NULL,
			user_agent varchar(255) DEFAULT NULL,
			blocked_fields text DEFAULT NULL,
			action_taken varchar(20) NOT NULL,
			raw_posted_excerpt text DEFAULT NULL,
			raw_posted_data text DEFAULT NULL,
			resolved_flag tinyint(1) NOT NULL DEFAULT 0,
			admin_note text DEFAULT NULL,
			PRIMARY KEY (id),
			KEY form_id (form_id),
			KEY action_taken (action_taken),
			KEY time (time),
			KEY resolved_flag (resolved_flag)
		) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // Store database version.
        update_option('cf7ac_db_version', '1.1');
    }

    /**
     * Insert a log entry.
     *
     * @param array $data Log data.
     * @return int|false Insert ID or false on failure.
     */
    public static function insert_log($data)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . self::TABLE_NAME;

        // Sanitize data.
        $sanitized_data = array(
            'form_id' => absint($data['form_id'] ?? 0),
            'ip' => self::sanitize_ip($data['ip'] ?? ''),
            'user_agent' => sanitize_text_field(substr($data['user_agent'] ?? '', 0, 255)),
            'blocked_fields' => wp_json_encode($data['blocked_fields'] ?? array()),
            'action_taken' => sanitize_key($data['action_taken'] ?? 'erase'),
            'raw_posted_excerpt' => sanitize_textarea_field(substr($data['raw_posted_excerpt'] ?? '', 0, 1000)),
            'raw_posted_data' => sanitize_textarea_field($data['raw_posted_data'] ?? ''),
            'resolved_flag' => absint($data['resolved_flag'] ?? 0),
            'admin_note' => sanitize_textarea_field($data['admin_note'] ?? ''),
        );

        $result = $wpdb->insert(
            $table_name,
            $sanitized_data,
            array(
                '%d', // form_id
                '%s', // ip
                '%s', // user_agent
                '%s', // blocked_fields
                '%s', // action_taken
                '%s', // raw_posted_excerpt
                '%s', // raw_posted_data
                '%d', // resolved_flag
                '%s', // admin_note
            )
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Get logs with pagination and filtering.
     *
     * @param array $args Query arguments.
     * @return array Array of log entries.
     */
    public static function get_logs($args = array())
    {
        global $wpdb;

        $table_name = $wpdb->prefix . self::TABLE_NAME;

        // Default arguments.
        $defaults = array(
            'per_page' => 20,
            'page' => 1,
            'form_id' => 0,
            'action_taken' => '',
            'date_from' => '',
            'date_to' => '',
            'search' => '',
            'resolved' => '',
            'orderby' => 'time',
            'order' => 'DESC',
        );

        $args = wp_parse_args($args, $defaults);

        // Build WHERE clause.
        $where = array('1=1');

        if (!empty($args['form_id'])) {
            $where[] = $wpdb->prepare('form_id = %d', absint($args['form_id']));
        }

        if (!empty($args['action_taken'])) {
            $where[] = $wpdb->prepare('action_taken = %s', sanitize_key($args['action_taken']));
        }

        if (!empty($args['date_from'])) {
            $where[] = $wpdb->prepare('DATE(time) >= %s', sanitize_text_field($args['date_from']));
        }

        if (!empty($args['date_to'])) {
            $where[] = $wpdb->prepare('DATE(time) <= %s', sanitize_text_field($args['date_to']));
        }

        if (!empty($args['search'])) {
            $search = '%' . $wpdb->esc_like(sanitize_text_field($args['search'])) . '%';
            $where[] = $wpdb->prepare('(raw_posted_excerpt LIKE %s OR admin_note LIKE %s)', $search, $search);
        }

        if ('' !== $args['resolved']) {
            $where[] = $wpdb->prepare('resolved_flag = %d', absint($args['resolved']));
        }

        $where_clause = implode(' AND ', $where);

        // Build ORDER BY clause.
        $allowed_orderby = array('time', 'form_id', 'action_taken', 'id');
        $orderby = in_array($args['orderby'], $allowed_orderby, true) ? $args['orderby'] : 'time';
        $order = 'ASC' === strtoupper($args['order']) ? 'ASC' : 'DESC';

        // Calculate offset.
        $offset = (absint($args['page']) - 1) * absint($args['per_page']);

        // Build query.
        $query = "SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";

        // Execute query.
        $results = $wpdb->get_results(
            $wpdb->prepare(
                $query,
                absint($args['per_page']),
                $offset
            ),
            ARRAY_A
        );

        return $results;
    }

    /**
     * Get total log count with filtering.
     *
     * @param array $args Query arguments.
     * @return int Total count.
     */
    public static function get_logs_count($args = array())
    {
        global $wpdb;

        $table_name = $wpdb->prefix . self::TABLE_NAME;

        // Build WHERE clause (same as get_logs).
        $where = array('1=1');

        if (!empty($args['form_id'])) {
            $where[] = $wpdb->prepare('form_id = %d', absint($args['form_id']));
        }

        if (!empty($args['action_taken'])) {
            $where[] = $wpdb->prepare('action_taken = %s', sanitize_key($args['action_taken']));
        }

        if (!empty($args['date_from'])) {
            $where[] = $wpdb->prepare('DATE(time) >= %s', sanitize_text_field($args['date_from']));
        }

        if (!empty($args['date_to'])) {
            $where[] = $wpdb->prepare('DATE(time) <= %s', sanitize_text_field($args['date_to']));
        }

        if (!empty($args['search'])) {
            $search = '%' . $wpdb->esc_like(sanitize_text_field($args['search'])) . '%';
            $where[] = $wpdb->prepare('(raw_posted_excerpt LIKE %s OR admin_note LIKE %s)', $search, $search);
        }

        if ('' !== ($args['resolved'] ?? '')) {
            $where[] = $wpdb->prepare('resolved_flag = %d', absint($args['resolved']));
        }

        $where_clause = implode(' AND ', $where);

        $query = "SELECT COUNT(*) FROM {$table_name} WHERE {$where_clause}";

        return (int) $wpdb->get_var($query);
    }

    /**
     * Update a log entry.
     *
     * @param int   $log_id Log ID.
     * @param array $data Data to update.
     * @return bool Success.
     */
    public static function update_log($log_id, $data)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . self::TABLE_NAME;

        // Sanitize data.
        $sanitized_data = array();

        if (isset($data['resolved_flag'])) {
            $sanitized_data['resolved_flag'] = absint($data['resolved_flag']);
        }

        if (isset($data['admin_note'])) {
            $sanitized_data['admin_note'] = sanitize_textarea_field($data['admin_note']);
        }

        if (empty($sanitized_data)) {
            return false;
        }

        $result = $wpdb->update(
            $table_name,
            $sanitized_data,
            array('id' => absint($log_id)),
            array_fill(0, count($sanitized_data), '%s'),
            array('%d')
        );

        return false !== $result;
    }

    /**
     * Delete logs older than specified days.
     *
     * @param int $days Number of days to retain.
     * @return int Number of deleted rows.
     */
    public static function delete_old_logs($days)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . self::TABLE_NAME;

        $date_threshold = gmdate('Y-m-d H:i:s', strtotime("-{$days} days"));

        $result = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table_name} WHERE time < %s",
                $date_threshold
            )
        );

        return (int) $result;
    }

    /**
     * Delete a single log entry.
     *
     * @param int $log_id Log ID.
     * @return bool Success.
     */
    public static function delete_log($log_id)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . self::TABLE_NAME;

        $result = $wpdb->delete(
            $table_name,
            array('id' => absint($log_id)),
            array('%d')
        );

        return false !== $result;
    }

    /**
     * Delete all logs.
     *
     * @return int Number of deleted rows.
     */
    public static function delete_all_logs()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . self::TABLE_NAME;

        $result = $wpdb->query("TRUNCATE TABLE {$table_name}");

        return (int) $result;
    }

    /**
     * Sanitize IP address for storage.
     *
     * @param string $ip IP address.
     * @return string Sanitized IP.
     */
    private static function sanitize_ip($ip)
    {
        // Validate and sanitize IP.
        $ip = filter_var($ip, FILTER_VALIDATE_IP);
        return $ip ? $ip : '';
    }

    /**
     * Get log by ID.
     *
     * @param int $log_id Log ID.
     * @return array|null Log data or null.
     */
    public static function get_log($log_id)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . self::TABLE_NAME;

        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE id = %d",
                absint($log_id)
            ),
            ARRAY_A
        );

        return $result;
    }
}
