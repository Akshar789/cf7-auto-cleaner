<?php
/**
 * Simplified Settings page admin interface.
 *
 * @package CF7_Auto_Cleaner
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Settings page class.
 */
class CF7AC_Settings_Page
{

    /**
     * Render settings page.
     */
    public static function render()
    {
        // Check permissions.
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'cf7-auto-cleaner'));
        }

        // Handle form submission.
        if (isset($_POST['cf7ac_save_settings'])) {
            self::save_settings();
        }

        // Get current settings.
        $core = CF7AC_Core::get_instance();
        $settings = $core->get_settings();

        // Render page.
        include CF7AC_PLUGIN_DIR . 'admin/views/settings-page.php';
    }

    /**
     * Save settings.
     */
    private static function save_settings()
    {
        // Verify nonce.
        if (!isset($_POST['cf7ac_settings_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cf7ac_settings_nonce'])), 'cf7ac_save_settings')) {
            wp_die(esc_html__('Security check failed.', 'cf7-auto-cleaner'));
        }

        // Sanitize and validate settings - simplified to essentials only.
        $new_settings = array(
            'enabled' => isset($_POST['cf7ac_enabled']),
            'default_action' => 'erase', // Fixed to erase only
            'show_user_notification' => isset($_POST['cf7ac_show_user_notification']),
            'notification_message' => sanitize_textarea_field($_POST['cf7ac_notification_message'] ?? ''),
            'blacklist' => sanitize_textarea_field($_POST['cf7ac_blacklist'] ?? ''),
            'whitelist' => sanitize_textarea_field($_POST['cf7ac_whitelist'] ?? ''),
        );

        // Update settings.
        $core = CF7AC_Core::get_instance();
        $core->update_settings($new_settings);

        // Show success message.
        add_settings_error(
            'cf7ac_messages',
            'cf7ac_message',
            __('Settings saved successfully.', 'cf7-auto-cleaner'),
            'success'
        );
    }
}
