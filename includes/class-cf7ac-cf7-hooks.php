<?php
/**
 * Contact Form 7 integration hooks.
 *
 * @package CF7_Auto_Cleaner
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * CF7 hooks handler.
 */
class CF7AC_CF7_Hooks
{

    /**
     * Sanitize CF7 submission before sending mail.
     *
     * @param WPCF7_ContactForm $contact_form Contact form object.
     * @return WPCF7_ContactForm
     */
    public static function sanitize_submission($contact_form)
    {
        // Get form ID.
        $form_id = $contact_form->id();

        // Check if plugin is enabled for this form.
        if (!self::is_enabled_for_form($form_id)) {
            return $contact_form;
        }

        // Get submission.
        $submission = WPCF7_Submission::get_instance();

        if (!$submission) {
            return $contact_form;
        }

        // Get posted data.
        $posted_data = $submission->get_posted_data();

        // Get settings.
        $settings = self::get_form_settings($form_id);

        // Get excluded fields.
        $excluded_fields = $settings['excluded_fields'] ?? array();

        // Initialize sanitizer.
        $sanitizer = new CF7AC_Sanitizer($settings);

        // Sanitize fields.
        $results = $sanitizer->sanitize_fields($posted_data, $excluded_fields);

        // Track if any modifications were made.
        $modified_fields = array();
        $all_matches = array();
        $has_banned_content = false;

        // Update posted data with sanitized values.
        foreach ($results as $field_name => $result) {
            if ($result['modified']) {
                $posted_data[$field_name] = $result['text'];
                $modified_fields[] = $field_name;
                $all_matches = array_merge($all_matches, $result['matches']);
                $has_banned_content = true;
            }
        }

        // Update submission data if modified.
        if (!empty($modified_fields)) {
            $submission->set_posted_data($posted_data);
        }

        return $contact_form;
    }

    /**
     * Validate CF7 submission (for block mode).
     *
     * @param WPCF7_Validation $result Validation result.
     * @param WPCF7_FormTag    $tag Form tag.
     * @return WPCF7_Validation
     */
    public static function validate_submission($result, $tag)
    {
        // Get form ID from current submission.
        $submission = WPCF7_Submission::get_instance();

        if (!$submission) {
            return $result;
        }

        $contact_form = $submission->get_contact_form();
        $form_id = $contact_form->id();

        // Check if plugin is enabled for this form.
        if (!self::is_enabled_for_form($form_id)) {
            return $result;
        }

        // Get settings.
        $settings = self::get_form_settings($form_id);

        // Only validate if action is 'block'.
        if ('block' !== ($settings['action'] ?? 'erase')) {
            return $result;
        }

        // Get field value.
        $field_name = $tag->name;
        $value = isset($_POST[$field_name]) ? wp_unslash($_POST[$field_name]) : '';

        // Skip if not a string.
        if (!is_string($value)) {
            return $result;
        }

        // Check if field is excluded.
        $excluded_fields = $settings['excluded_fields'] ?? array();
        if (in_array($field_name, $excluded_fields, true)) {
            return $result;
        }

        // Initialize sanitizer.
        $sanitizer = new CF7AC_Sanitizer($settings);

        // Check if should be blocked.
        if ($sanitizer->should_block($value)) {
            $result->invalidate(
                $tag,
                __('Your message contains disallowed content. Please remove it and try again.', 'cf7-auto-cleaner')
            );
        }

        return $result;
    }

    /**
     * Check if plugin is enabled for a specific form.
     *
     * @param int $form_id Form ID.
     * @return bool
     */
    private static function is_enabled_for_form($form_id)
    {
        // Check global setting.
        $core = CF7AC_Core::get_instance();
        $enabled = $core->is_enabled();

        if (!$enabled) {
            return false;
        }

        // Check per-form override.
        $per_form_enabled = get_post_meta($form_id, 'cf7ac_enabled', true);

        // If per-form setting exists, use it; otherwise use global.
        if ('' !== $per_form_enabled) {
            return (bool) $per_form_enabled;
        }

        return true;
    }

    /**
     * Get settings for a specific form (merged global + per-form).
     *
     * @param int $form_id Form ID.
     * @return array Settings.
     */
    private static function get_form_settings($form_id)
    {
        $core = CF7AC_Core::get_instance();
        $global_settings = $core->get_settings();

        // Get per-form overrides.
        $per_form_action = get_post_meta($form_id, 'cf7ac_action', true);
        $per_form_blacklist = get_post_meta($form_id, 'cf7ac_blacklist', true);
        $per_form_whitelist = get_post_meta($form_id, 'cf7ac_whitelist', true);
        $per_form_excluded_fields = get_post_meta($form_id, 'cf7ac_excluded_fields', true);

        // Merge blacklists.
        $blacklist = array_filter(array_map('trim', explode("\n", $global_settings['blacklist'] ?? '')));
        if (!empty($per_form_blacklist)) {
            $per_form_list = array_filter(array_map('trim', explode("\n", $per_form_blacklist)));
            $blacklist = array_unique(array_merge($blacklist, $per_form_list));
        }

        // Merge whitelists.
        $whitelist = array_filter(array_map('trim', explode("\n", $global_settings['whitelist'] ?? '')));
        if (!empty($per_form_whitelist)) {
            $per_form_list = array_filter(array_map('trim', explode("\n", $per_form_whitelist)));
            $whitelist = array_unique(array_merge($whitelist, $per_form_list));
        }

        // Build settings.
        $settings = array(
            'action' => !empty($per_form_action) ? $per_form_action : ($global_settings['default_action'] ?? 'erase'),
            'replace_mask' => $global_settings['replace_mask'] ?? '*****',
            'erase_behavior' => $global_settings['erase_behavior'] ?? 'erase_word_only',
            'blacklist' => $blacklist,
            'whitelist' => $whitelist,
            'fuzzy_matching' => $global_settings['fuzzy_matching'] ?? false,
            'fuzzy_threshold' => $global_settings['fuzzy_threshold'] ?? 2,
            'use_fast_matcher' => $global_settings['use_fast_matcher'] ?? false,
            'excluded_fields' => !empty($per_form_excluded_fields) ? explode(',', $per_form_excluded_fields) : array(),
        );

        return $settings;
    }

    /**
     * Log sanitization event.
     *
     * @param int    $form_id Form ID.
     * @param array  $fields Modified fields.
     * @param array  $matches Detected matches.
     * @param string $action Action taken.
     */
    private static function log_sanitization($form_id, $fields, $matches, $action)
    {
        // Check if logging is enabled.
        $core = CF7AC_Core::get_instance();
        if (!$core->get_setting('log_submissions', true)) {
            return;
        }

        // Rate limit logging.
        $rate_limit_key = 'cf7ac_log_rate_limit_' . md5(serialize($fields) . $form_id);
        if (get_transient($rate_limit_key)) {
            return;
        }
        set_transient($rate_limit_key, true, 60); // 1 minute rate limit.

        // Prepare log data.
        $log_data = array(
            'form_id' => $form_id,
            'ip' => self::get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '',
            'blocked_fields' => $fields,
            'action_taken' => $action,
            'raw_posted_excerpt' => self::get_posted_excerpt(),
            'raw_posted_data' => self::get_full_posted_data(),
            'resolved_flag' => 0,
            'admin_note' => '',
        );

        // Insert log.
        CF7AC_Database::insert_log($log_data);

        // Send admin notification if enabled.
        self::maybe_send_admin_notification($form_id, $fields, $matches, $action);
    }

    /**
     * Get client IP address.
     *
     * @return string IP address.
     */
    private static function get_client_ip()
    {
        $ip = '';

        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_CLIENT_IP']));
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
        }

        return $ip;
    }

    /**
     * Get truncated excerpt of posted data.
     *
     * @return string Excerpt.
     */
    private static function get_posted_excerpt()
    {
        // Fields to exclude (CF7 internal fields).
        $excluded_fields = array(
            '_wpcf7',
            '_wpcf7_version',
            '_wpcf7_locale',
            '_wpcf7_unit_tag',
            '_wpcf7_container_post',
            '_wpcf7_posted_data_hash',
            '_wpcf7_recaptcha_response',
            'g-recaptcha-response',
            '_wpnonce',
            '_wp_http_referer',
        );

        // Build readable excerpt from user fields only.
        $excerpt_parts = array();

        foreach ($_POST as $key => $value) {
            // Skip CF7 internal fields.
            if (in_array($key, $excluded_fields, true) || strpos($key, '_wpcf7') === 0) {
                continue;
            }

            // Skip empty values.
            if (empty($value)) {
                continue;
            }

            // Format the field nicely.
            if (is_string($value)) {
                // Clean up field name (remove dashes, capitalize).
                $field_label = ucwords(str_replace(array('-', '_'), ' ', $key));

                // Truncate long values.
                $field_value = strlen($value) > 100 ? substr($value, 0, 100) . '...' : $value;

                // Add to excerpt.
                $excerpt_parts[] = $field_label . ': ' . $field_value;
            } elseif (is_array($value)) {
                $field_label = ucwords(str_replace(array('-', '_'), ' ', $key));
                $excerpt_parts[] = $field_label . ': ' . implode(', ', $value);
            }
        }

        // Join all parts.
        $excerpt = implode(' | ', $excerpt_parts);

        // Return truncated excerpt.
        return substr($excerpt, 0, 500);
    }

    /**
     * Get full posted data for storage.
     *
     * @return string Full posted data.
     */
    private static function get_full_posted_data()
    {
        // Fields to exclude (CF7 internal fields).
        $excluded_fields = array(
            '_wpcf7',
            '_wpcf7_version',
            '_wpcf7_locale',
            '_wpcf7_unit_tag',
            '_wpcf7_container_post',
            '_wpcf7_posted_data_hash',
            '_wpcf7_recaptcha_response',
            'g-recaptcha-response',
            '_wpnonce',
            '_wp_http_referer',
        );

        // Build readable full content from user fields only.
        $content_parts = array();

        foreach ($_POST as $key => $value) {
            // Skip CF7 internal fields.
            if (in_array($key, $excluded_fields, true) || strpos($key, '_wpcf7') === 0) {
                continue;
            }

            // Skip empty values.
            if (empty($value)) {
                continue;
            }

            // Format the field nicely.
            if (is_string($value)) {
                // Clean up field name (remove dashes, capitalize).
                $field_label = ucwords(str_replace(array('-', '_'), ' ', $key));
                $content_parts[] = $field_label . ': ' . $value;
            } elseif (is_array($value)) {
                $field_label = ucwords(str_replace(array('-', '_'), ' ', $key));
                $content_parts[] = $field_label . ': ' . implode(', ', $value);
            }
        }

        // Join all parts with line breaks for better readability.
        return implode("\n", $content_parts);
    }

    /**
     * Maybe send admin notification email.
     *
     * @param int    $form_id Form ID.
     * @param array  $fields Modified fields.
     * @param array  $matches Detected matches.
     * @param string $action Action taken.
     */
    private static function maybe_send_admin_notification($form_id, $fields, $matches, $action)
    {
        $core = CF7AC_Core::get_instance();

        // Check if admin notifications are enabled.
        $admin_email = $core->get_setting('admin_notification_email', '');
        if (empty($admin_email)) {
            return;
        }

        // Only send for block and flag actions.
        if (!in_array($action, array('block', 'flag_only'), true)) {
            return;
        }

        // Build email.
        $subject = sprintf(
            /* translators: %s: Form ID */
            __('[CF7 Auto Cleaner] Content flagged in form #%d', 'cf7-auto-cleaner'),
            $form_id
        );

        $message = sprintf(
            /* translators: 1: Form ID, 2: Action, 3: Fields, 4: Matches */
            __("A submission to form #%1\$d was %2\$s.\n\nFields: %3\$s\n\nMatched words: %4\$s\n\nPlease review in the CF7 Auto Cleaner logs.", 'cf7-auto-cleaner'),
            $form_id,
            $action,
            implode(', ', $fields),
            implode(', ', array_column($matches, 'word'))
        );

        wp_mail($admin_email, $subject, $message);
    }
}
