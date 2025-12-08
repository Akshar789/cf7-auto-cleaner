<?php
/**
 * Per-form settings meta box.
 *
 * @package CF7_Auto_Cleaner
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Per-form settings class.
 */
class CF7AC_Per_Form_Settings
{

    /**
     * Render meta box.
     *
     * @param WP_Post $post Current post object.
     */
    public static function render_meta_box($post)
    {
        // Get current settings.
        $enabled = get_post_meta($post->ID, 'cf7ac_enabled', true);
        $action = get_post_meta($post->ID, 'cf7ac_action', true);
        $blacklist = get_post_meta($post->ID, 'cf7ac_blacklist', true);
        $whitelist = get_post_meta($post->ID, 'cf7ac_whitelist', true);
        $excluded_fields = get_post_meta($post->ID, 'cf7ac_excluded_fields', true);

        // Nonce field.
        wp_nonce_field('cf7ac_save_per_form', 'cf7ac_per_form_nonce');
        ?>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="cf7ac_enabled"><?php esc_html_e('Enable for this form', 'cf7-auto-cleaner'); ?></label>
                </th>
                <td>
                    <input type="checkbox" name="cf7ac_enabled" id="cf7ac_enabled" value="1" <?php checked($enabled, '1'); ?>>
                    <p class="description">
                        <?php esc_html_e('Enable CF7 Auto Cleaner for this specific form. Leave unchecked to use global setting.', 'cf7-auto-cleaner'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="cf7ac_action"><?php esc_html_e('Action Override', 'cf7-auto-cleaner'); ?></label>
                </th>
                <td>
                    <select name="cf7ac_action" id="cf7ac_action">
                        <option value=""><?php esc_html_e('Use global setting', 'cf7-auto-cleaner'); ?></option>
                        <option value="erase" <?php selected($action, 'erase'); ?>>
                            <?php esc_html_e('Erase', 'cf7-auto-cleaner'); ?></option>
                        <option value="replace" <?php selected($action, 'replace'); ?>>
                            <?php esc_html_e('Replace', 'cf7-auto-cleaner'); ?></option>
                        <option value="block" <?php selected($action, 'block'); ?>>
                            <?php esc_html_e('Block', 'cf7-auto-cleaner'); ?></option>
                        <option value="flag_only" <?php selected($action, 'flag_only'); ?>>
                            <?php esc_html_e('Flag only', 'cf7-auto-cleaner'); ?></option>
                    </select>
                    <p class="description">
                        <?php esc_html_e('Override the default action for this form.', 'cf7-auto-cleaner'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="cf7ac_excluded_fields"><?php esc_html_e('Excluded Fields', 'cf7-auto-cleaner'); ?></label>
                </th>
                <td>
                    <input type="text" name="cf7ac_excluded_fields" id="cf7ac_excluded_fields"
                        value="<?php echo esc_attr($excluded_fields); ?>" class="regular-text">
                    <p class="description">
                        <?php esc_html_e('Comma-separated list of field names to skip (e.g., "your-name,your-email").', 'cf7-auto-cleaner'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="cf7ac_blacklist"><?php esc_html_e('Additional Blacklist', 'cf7-auto-cleaner'); ?></label>
                </th>
                <td>
                    <textarea name="cf7ac_blacklist" id="cf7ac_blacklist" rows="5"
                        class="large-text code"><?php echo esc_textarea($blacklist); ?></textarea>
                    <p class="description">
                        <?php esc_html_e('Additional words/phrases for this form only (merged with global blacklist).', 'cf7-auto-cleaner'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="cf7ac_whitelist"><?php esc_html_e('Additional Whitelist', 'cf7-auto-cleaner'); ?></label>
                </th>
                <td>
                    <textarea name="cf7ac_whitelist" id="cf7ac_whitelist" rows="5"
                        class="large-text code"><?php echo esc_textarea($whitelist); ?></textarea>
                    <p class="description">
                        <?php esc_html_e('Additional whitelist entries for this form only (merged with global whitelist).', 'cf7-auto-cleaner'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <?php
    }

    /**
     * Save meta box data.
     *
     * @param int $post_id Post ID.
     */
    public static function save_meta_box($post_id)
    {
        // Check if this is an autosave.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check post type.
        if ('wpcf7_contact_form' !== get_post_type($post_id)) {
            return;
        }

        // Verify nonce.
        if (!isset($_POST['cf7ac_per_form_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cf7ac_per_form_nonce'])), 'cf7ac_save_per_form')) {
            return;
        }

        // Check permissions.
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save settings.
        $enabled = isset($_POST['cf7ac_enabled']) ? '1' : '0';
        update_post_meta($post_id, 'cf7ac_enabled', $enabled);

        $action = isset($_POST['cf7ac_action']) ? sanitize_key($_POST['cf7ac_action']) : '';
        update_post_meta($post_id, 'cf7ac_action', $action);

        $blacklist = isset($_POST['cf7ac_blacklist']) ? sanitize_textarea_field($_POST['cf7ac_blacklist']) : '';
        update_post_meta($post_id, 'cf7ac_blacklist', $blacklist);

        $whitelist = isset($_POST['cf7ac_whitelist']) ? sanitize_textarea_field($_POST['cf7ac_whitelist']) : '';
        update_post_meta($post_id, 'cf7ac_whitelist', $whitelist);

        $excluded_fields = isset($_POST['cf7ac_excluded_fields']) ? sanitize_text_field($_POST['cf7ac_excluded_fields']) : '';
        update_post_meta($post_id, 'cf7ac_excluded_fields', $excluded_fields);
    }
}
