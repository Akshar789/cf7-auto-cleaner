<?php
/**
 * Settings page view template.
 *
 * @package CF7_Auto_Cleaner
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('Auto Cleaner Settings', 'cf7-auto-cleaner'); ?></h1>



<?php settings_errors('cf7ac_messages'); ?>

<form method="post" action="">
    <?php wp_nonce_field('cf7ac_save_settings', 'cf7ac_settings_nonce'); ?>

    <div class="cf7ac-card">
        <h2><?php esc_html_e('General Settings', 'cf7-auto-cleaner'); ?></h2>
        <table class="form-table">
            <!-- Global Enable -->
            <tr>
                <th scope="row">
                    <label for="cf7ac_enabled"><?php esc_html_e('Enable Plugin', 'cf7-auto-cleaner'); ?></label>
                </th>
                <td>
                    <label class="switch">
                        <input type="checkbox" name="cf7ac_enabled" id="cf7ac_enabled" value="1" <?php checked($settings['enabled'] ?? true); ?>>
                        <span class="slider round"></span>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Enable automatic word filtering for all Contact Form 7 forms.', 'cf7-auto-cleaner'); ?>
                    </p>
                </td>
            </tr>

            <!-- Show User Notification -->
            <tr>
                <th scope="row">
                    <label
                        for="cf7ac_show_user_notification"><?php esc_html_e('Show User Notification', 'cf7-auto-cleaner'); ?></label>
                </th>
                <td>
                    <input type="checkbox" name="cf7ac_show_user_notification" id="cf7ac_show_user_notification"
                        value="1" <?php checked($settings['show_user_notification'] ?? true); ?>>
                    <p class="description">
                        <?php esc_html_e('Show a notification to users when content is removed.', 'cf7-auto-cleaner'); ?>
                    </p>
                </td>
            </tr>

            <!-- Notification Message -->
            <tr>
                <th scope="row">
                    <label
                        for="cf7ac_notification_message"><?php esc_html_e('Notification Message', 'cf7-auto-cleaner'); ?></label>
                </th>
                <td>
                    <input type="text" name="cf7ac_notification_message" id="cf7ac_notification_message"
                        value="<?php echo esc_attr($settings['notification_message'] ?? __('We removed disallowed words from your message.', 'cf7-auto-cleaner')); ?>"
                        class="large-text">
                    <p class="description">
                        <?php esc_html_e('Message shown to users when content is modified.', 'cf7-auto-cleaner'); ?>
                    </p>
                </td>
            </tr>
        </table>
    </div>

    <div class="cf7ac-card">
        <h2><?php esc_html_e('Blacklist & Whitelist', 'cf7-auto-cleaner'); ?></h2>
        <table class="form-table">
            <!-- Blacklist -->
            <tr>
                <th scope="row">
                    <label for="cf7ac_blacklist"><?php esc_html_e('Blacklist', 'cf7-auto-cleaner'); ?></label>
                </th>
                <td>
                    <textarea name="cf7ac_blacklist" id="cf7ac_blacklist" rows="10" class="large-text code"
                        placeholder="badword1&#10;badword2"><?php echo esc_textarea($settings['blacklist'] ?? ''); ?></textarea>
                    <p class="description">
                        <?php esc_html_e('Enter banned words, one per line. These will be automatically removed from form submissions.', 'cf7-auto-cleaner'); ?>
                    </p>
                </td>
            </tr>

            <!-- Whitelist -->
            <tr>
                <th scope="row">
                    <label for="cf7ac_whitelist"><?php esc_html_e('Whitelist', 'cf7-auto-cleaner'); ?></label>
                </th>
                <td>
                    <textarea name="cf7ac_whitelist" id="cf7ac_whitelist" rows="10" class="large-text code"
                        placeholder="goodword1&#10;goodword2"><?php echo esc_textarea($settings['whitelist'] ?? ''); ?></textarea>
                    <p class="description">
                        <?php esc_html_e('Words that should never be blocked (e.g., "assess", "classic"). This prevents false positives.', 'cf7-auto-cleaner'); ?>
                    </p>
                </td>
            </tr>
        </table>
    </div>



    <?php submit_button(__('Save Settings', 'cf7-auto-cleaner'), 'primary', 'cf7ac_save_settings'); ?>
</form>
</div>