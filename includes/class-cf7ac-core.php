<?php
/**
 * Core plugin class.
 *
 * @package CF7_Auto_Cleaner
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Core plugin class - Singleton pattern.
 */
class CF7AC_Core
{

    /**
     * Single instance of the class.
     *
     * @var CF7AC_Core
     */
    private static $instance = null;

    /**
     * Plugin settings.
     *
     * @var array
     */
    private $settings;

    /**
     * Get singleton instance.
     *
     * @return CF7AC_Core
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - private to enforce singleton.
     */
    private function __construct()
    {
        $this->load_settings();
        $this->init_hooks();
    }

    /**
     * Load plugin settings.
     */
    private function load_settings()
    {
        $this->settings = get_option('cf7ac_settings', array());
    }

    /**
     * Initialize WordPress hooks.
     */
    private function init_hooks()
    {
        // Admin hooks.
        if (is_admin()) {
            add_action('admin_menu', array($this, 'register_admin_menu'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        }

        // Frontend hooks.
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));

        // CF7 hooks - Load the hooks class and register.
        if (class_exists('WPCF7')) {
            require_once CF7AC_PLUGIN_DIR . 'includes/class-cf7ac-cf7-hooks.php';
            add_action('wpcf7_before_send_mail', array('CF7AC_CF7_Hooks', 'sanitize_submission'), 10);
            add_filter('wpcf7_validate', array('CF7AC_CF7_Hooks', 'validate_submission'), 10, 2);
        }

        // Scheduled events.
        if (!wp_next_scheduled('cf7ac_cleanup_logs')) {
            wp_schedule_event(time(), 'daily', 'cf7ac_cleanup_logs');
        }
        add_action('cf7ac_cleanup_logs', array($this, 'cleanup_old_logs'));
    }

    /**
     * Register admin menu pages.
     */
    public function register_admin_menu()
    {
        // Add settings page under Contact Form 7 menu.
        add_submenu_page(
            'wpcf7',
            __('Auto Cleaner Settings', 'cf7-auto-cleaner'),
            __('Auto Cleaner', 'cf7-auto-cleaner'),
            'manage_options',
            'cf7-auto-cleaner',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Render settings page.
     */
    public function render_settings_page()
    {
        if (!class_exists('CF7AC_Settings_Page')) {
            require_once CF7AC_PLUGIN_DIR . 'admin/class-cf7ac-settings-page.php';
        }
        CF7AC_Settings_Page::render();
    }



    /**
     * Enqueue admin assets.
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_admin_assets($hook)
    {
        // Only load on our admin pages and CF7 form edit pages.
        if (strpos($hook, 'cf7-auto-cleaner') === false && 'post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'cf7ac-admin',
            CF7AC_PLUGIN_URL . 'assets/css/cf7ac-admin.css',
            array(),
            CF7AC_VERSION
        );

        wp_enqueue_script(
            'cf7ac-admin',
            CF7AC_PLUGIN_URL . 'assets/js/cf7ac-admin.js',
            array('jquery'),
            CF7AC_VERSION,
            true
        );
    }

    /**
     * Enqueue frontend assets.
     */
    public function enqueue_frontend_assets()
    {
        // Only load if CF7 Auto Cleaner is enabled.
        if (!$this->is_enabled()) {
            return;
        }

        // Only load on pages with CF7 forms.
        if (!function_exists('wpcf7_contact_form')) {
            return;
        }

        wp_enqueue_script(
            'cf7ac-client',
            CF7AC_PLUGIN_URL . 'assets/js/cf7ac-client.js',
            array(),
            CF7AC_VERSION,
            true
        );

        // Pass settings to JavaScript.
        $config = $this->get_client_config();
        wp_localize_script('cf7ac-client', 'cf7acConfig', $config);
    }

    /**
     * Get client-side configuration.
     *
     * @return array Configuration for JavaScript.
     */
    private function get_client_config()
    {
        $blacklist = $this->get_setting('blacklist', '');
        $whitelist = $this->get_setting('whitelist', '');

        return array(
            'enabled' => $this->is_enabled(),
            'action' => $this->get_setting('default_action', 'erase'),
            'replaceMask' => $this->get_setting('replace_mask', '*****'),
            'eraseBehavior' => $this->get_setting('erase_behavior', 'erase_word_only'),
            'showNotification' => $this->get_setting('show_user_notification', true),
            'notificationMessage' => $this->get_setting('notification_message', ''),
            'blacklist' => array_filter(array_map('trim', explode("\n", $blacklist))),
            'whitelist' => array_filter(array_map('trim', explode("\n", $whitelist))),
        );
    }



    /**
     * Cleanup old logs based on retention settings.
     */
    public function cleanup_old_logs()
    {
        if (!class_exists('CF7AC_Logger')) {
            require_once CF7AC_PLUGIN_DIR . 'includes/class-cf7ac-logger.php';
        }
        CF7AC_Logger::cleanup_old_logs();
    }

    /**
     * Check if plugin is enabled globally.
     *
     * @return bool
     */
    public function is_enabled()
    {
        return (bool) $this->get_setting('enabled', true);
    }

    /**
     * Get a setting value.
     *
     * @param string $key Setting key.
     * @param mixed  $default Default value.
     * @return mixed
     */
    public function get_setting($key, $default = null)
    {
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }

    /**
     * Get all settings.
     *
     * @return array
     */
    public function get_settings()
    {
        return $this->settings;
    }

    /**
     * Update settings.
     *
     * @param array $new_settings New settings.
     */
    public function update_settings($new_settings)
    {
        $this->settings = $new_settings;
        update_option('cf7ac_settings', $new_settings);

        // Clear cache when settings change.
        $this->clear_cache();
    }

    /**
     * Clear all plugin caches.
     */
    public function clear_cache()
    {
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_cf7ac_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_cf7ac_%'");
    }
}
