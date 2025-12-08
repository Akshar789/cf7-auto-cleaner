<?php
/**
 * Plugin Name: CF7 Auto Cleaner — Auto Erase Profanity & Promotional Content
 * Plugin URI: https://github.com/Akshar789/cf7-auto-cleaner
 * Description: Automatically filters profanity and promotional content from Contact Form 7 submissions with client-side and server-side sanitization.
 * Version: 1.0.1
 * Author: Akshar
 * Author URI: https://github.com/Akshar789
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: cf7-auto-cleaner
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Requires Plugins: contact-form-7
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Plugin version.
 */
define( 'CF7AC_VERSION', '1.0.1' );

/**
 * Plugin directory path.
 */
define( 'CF7AC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 */
define( 'CF7AC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin basename.
 */
define( 'CF7AC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Check if Contact Form 7 is active.
 */
function cf7ac_check_dependencies() {
	if ( ! class_exists( 'WPCF7' ) ) {
		add_action( 'admin_notices', 'cf7ac_missing_cf7_notice' );
		deactivate_plugins( CF7AC_PLUGIN_BASENAME );
		return false;
	}
	return true;
}

/**
 * Display admin notice if Contact Form 7 is not active.
 */
function cf7ac_missing_cf7_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<?php
			esc_html_e(
				'CF7 Auto Cleaner requires Contact Form 7 to be installed and activated.',
				'cf7-auto-cleaner'
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Autoloader for plugin classes.
 *
 * @param string $class_name The class name to load.
 */
function cf7ac_autoloader( $class_name ) {
	// Check if the class uses our prefix.
	if ( strpos( $class_name, 'CF7AC_' ) !== 0 ) {
		return;
	}

	// Convert class name to file name.
	$class_file = 'class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';

	// Define possible paths.
	$paths = array(
		CF7AC_PLUGIN_DIR . 'includes/' . $class_file,
		CF7AC_PLUGIN_DIR . 'admin/' . $class_file,
	);

	// Try to load the file.
	foreach ( $paths as $path ) {
		if ( file_exists( $path ) ) {
			require_once $path;
			return;
		}
	}
}
spl_autoload_register( 'cf7ac_autoloader' );

/**
 * Plugin activation hook.
 */
function cf7ac_activate() {
	// Check dependencies.
	if ( ! cf7ac_check_dependencies() ) {
		return;
	}

	// Create database tables.
	require_once CF7AC_PLUGIN_DIR . 'includes/class-cf7ac-database.php';
	CF7AC_Database::create_tables();

	// Set default options.
	cf7ac_set_default_options();

	// Flush rewrite rules.
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'cf7ac_activate' );

/**
 * Plugin deactivation hook.
 */
function cf7ac_deactivate() {
	// Clear scheduled events.
	wp_clear_scheduled_hook( 'cf7ac_cleanup_logs' );
	wp_clear_scheduled_hook( 'cf7ac_send_admin_summary' );

	// Flush rewrite rules.
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'cf7ac_deactivate' );

/**
 * Set default plugin options.
 */
function cf7ac_set_default_options() {
	$defaults = array(
		'enabled'                    => true,
		'default_action'             => 'erase',
		'replace_mask'               => '*****',
		'erase_behavior'             => 'erase_word_only',
		'show_user_notification'     => true,
		'notification_message'       => __( 'We removed disallowed words from your message.', 'cf7-auto-cleaner' ),
		'blacklist'                  => cf7ac_get_default_blacklist(),
		'whitelist'                  => cf7ac_get_default_whitelist(),
		'fuzzy_matching'             => false,
		'fuzzy_threshold'            => 2,
		'use_fast_matcher'           => false,
		'external_api_enabled'       => false,
		'external_api_provider'      => '',
		'external_api_key'           => '',
		'log_submissions'            => true,
		'log_retention_days'         => 30,
		'max_logs'                   => 10000,
		'admin_notification_email'   => get_option( 'admin_email' ),
		'cache_enabled'              => true,
		'debug_mode'                 => false,
		'performance_mode'           => 'medium',
		'store_full_content'         => false,
	);

	add_option( 'cf7ac_settings', $defaults );
}

/**
 * Get default blacklist.
 *
 * @return string Default blacklist (one per line).
 */
function cf7ac_get_default_blacklist() {
	return implode(
		"\n",
		array(
			// Spam & Scam
			'spam',
			'scam',
			'fraud',
			'phishing',
			
			// Gambling
			'casino',
			'lottery',
			'poker',
			'betting',
			'jackpot',
			'gamble',
			
			// Promotional
			'click here',
			'buy now',
			'limited time',
			'act now',
			'order now',
			'sign up now',
			'register now',
			'subscribe now',
			'download now',
			
			// Money schemes
			'free money',
			'make money fast',
			'get rich quick',
			'work from home',
			'guaranteed income',
			'no risk',
			'risk free',
			'100% free',
			
			// Urgency tactics
			'urgent',
			'hurry up',
			'last chance',
			'don\'t miss',
			'limited offer',
			'special promotion',
			'exclusive offer',
			'once in a lifetime',
			
			// Suspicious
			'verify your account',
			'confirm your identity',
			'update your information',
			'suspended account',
			'unusual activity',
			'security alert',
			
			// Cryptocurrency
			'bitcoin',
			'crypto',
			'forex',
			'trading signals',
			
			// Adult/Pharmaceutical
			'pills',
			'medication',
			'prescription',
			'pharmacy',
		)
	);
}

/**
 * Get default whitelist.
 *
 * @return string Default whitelist (one per line).
 */
function cf7ac_get_default_whitelist() {
	return implode(
		"\n",
		array(
			'assess',
			'classic',
			'glass',
			'assignment',
		)
	);
}

/**
 * Initialize the plugin.
 */
function cf7ac_init() {
	// Check dependencies.
	if ( ! cf7ac_check_dependencies() ) {
		return;
	}

	// Load text domain.
	load_plugin_textdomain(
		'cf7-auto-cleaner',
		false,
		dirname( CF7AC_PLUGIN_BASENAME ) . '/languages'
	);

	// Initialize core.
	CF7AC_Core::get_instance();
}
add_action( 'plugins_loaded', 'cf7ac_init' );

/**
 * Uninstall hook - clean up all plugin data.
 */
function cf7ac_uninstall() {
	global $wpdb;

	// Delete options.
	delete_option( 'cf7ac_settings' );

	// Delete all per-form settings (post meta).
	$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'cf7ac_%'" );

	// Drop database table.
	$table_name = $wpdb->prefix . 'cf7ac_logs';
	$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );

	// Clear all transients.
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_cf7ac_%'" );
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_cf7ac_%'" );
}
register_uninstall_hook( __FILE__, 'cf7ac_uninstall' );
