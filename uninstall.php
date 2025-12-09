<?php
/**
 * Uninstall script for CF7 Auto Cleaner.
 *
 * This file is executed when the plugin is deleted via WordPress admin.
 * It removes all plugin data from the database.
 *
 * @package CF7_Auto_Cleaner
 */

// If uninstall not called from WordPress, exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options.
delete_option('cf7ac_settings');
delete_option('cf7ac_db_version');

// Delete all post meta for CF7 forms.
global $wpdb;

$wpdb->query(
    "DELETE FROM {$wpdb->postmeta} 
    WHERE meta_key LIKE 'cf7ac_%'"
);

// Drop custom database table if it exists.
$table_name = $wpdb->prefix . 'cf7ac_logs';
$wpdb->query("DROP TABLE IF EXISTS {$table_name}");

// Clear any cached data.
wp_cache_flush();

// Delete transients.
$wpdb->query(
    "DELETE FROM {$wpdb->options} 
    WHERE option_name LIKE '_transient_cf7ac_%' 
    OR option_name LIKE '_transient_timeout_cf7ac_%'"
);

// Remove scheduled events.
$timestamp = wp_next_scheduled('cf7ac_cleanup_logs');
if ($timestamp) {
    wp_unschedule_event($timestamp, 'cf7ac_cleanup_logs');
}
