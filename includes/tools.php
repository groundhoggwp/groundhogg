<?php

use Groundhogg\Queue\Event_Queue;
use Groundhogg\Utils\DateTimeHelper;
use function Groundhogg\extrapolate_wp_mail_plugin;
use function Groundhogg\get_default_from_name;
use function Groundhogg\gh_cron_installed;

/**
 * Get system info
 *
 * @since       2.0
 * @return      string $return A string containing the info to output
 * @global      object $wpdb Used to query the database using the WordPress Database API
 */
function groundhogg_tools_sysinfo_get() {

	global $wpdb;

	$plugin = \Groundhogg\Plugin::instance();

	if ( ! class_exists( 'Browser' ) ) {
		require_once GROUNDHOGG_PATH . 'includes/lib/browser.php';
	}

	$browser = new \Browser();

	// Get theme info
	$theme_data   = wp_get_theme();
	$theme        = $theme_data->Name . ' ' . $theme_data->Version;
	$parent_theme = $theme_data->Template;
	if ( ! empty( $parent_theme ) ) {
		$parent_theme_data = wp_get_theme( $parent_theme );
		$parent_theme      = $parent_theme_data->Name . ' ' . $parent_theme_data->Version;
	}

	// Try to identify the hosting provider
	$host = groundhogg_get_host();

	$return = '### Begin System Info ###' . "\n\n";

	// Start with the basics...
	$return .= '-- Site Info' . "\n\n";
	$return .= 'Site URL:                 ' . site_url() . "\n";
	$return .= 'Home URL:                 ' . home_url() . "\n";
	$return .= 'Multisite:                ' . ( is_multisite() ? 'Yes' : 'No' ) . "\n";

	$return = apply_filters( 'groundhogg_sysinfo_after_site_info', $return );

	// Can we determine the site's host?
	if ( $host ) {
		$return .= "\n" . '-- Hosting Provider' . "\n\n";
		$return .= 'Host:                     ' . $host . "\n";

		$return = apply_filters( 'groundhogg_sysinfo_after_host_info', $return );
	}

	// The local users' browser information, handled by the Browser class
	$return .= "\n" . '-- User Browser' . "\n\n";
	$return .= $browser;

	$return = apply_filters( 'groundhogg_sysinfo_after_user_browser', $return );

	$locale = get_locale();

	// WordPress configuration
	$return .= "\n" . '-- WordPress Configuration' . "\n\n";
	$return .= 'Version:                  ' . get_bloginfo( 'version' ) . "\n";
	$return .= 'Language:                 ' . ( ! empty( $locale ) ? $locale : 'en_US' ) . "\n";
	$return .= 'Permalink Structure:      ' . ( get_option( 'permalink_structure' ) ? get_option( 'permalink_structure' ) : 'Default' ) . "\n";
	$return .= 'Active Theme:             ' . $theme . "\n";
	if ( $parent_theme !== $theme ) {
		$return .= 'Parent Theme:             ' . $parent_theme . "\n";
	}
	$return .= 'Show On Front:            ' . get_option( 'show_on_front' ) . "\n";

	// Only show page specs if frontpage is set to 'page'
	if ( get_option( 'show_on_front' ) == 'page' ) {
		$front_page_id = get_option( 'page_on_front' );
		$blog_page_id  = get_option( 'page_for_posts' );

		$return .= 'Page On Front:            ' . ( $front_page_id != 0 ? get_the_title( $front_page_id ) . ' (#' . $front_page_id . ')' : 'Unset' ) . "\n";
		$return .= 'Page For Posts:           ' . ( $blog_page_id != 0 ? get_the_title( $blog_page_id ) . ' (#' . $blog_page_id . ')' : 'Unset' ) . "\n";
	}

	$return .= 'ABSPATH:                  ' . ABSPATH . "\n";

	// Make sure wp_remote_post() is working
	$request['cmd'] = '_notify-validate';

	$params = array(
		'sslverify'  => false,
		'timeout'    => 60,
		'user-agent' => 'Groundhogg/' . GROUNDHOGG_VERSION,
		'body'       => $request
	);

	$response = wp_remote_post( 'https://webhook.site/TestPost', $params );

	if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
		$WP_REMOTE_POST = 'wp_remote_post() works';
	} else {
		$WP_REMOTE_POST = 'wp_remote_post() does not work';
	}

	$return .= 'Remote Post:              ' . $WP_REMOTE_POST . "\n";
	$return .= 'Table Prefix:             ' . 'Length: ' . strlen( $wpdb->prefix ) . '   Status: ' . ( strlen( $wpdb->prefix ) > 16 ? 'ERROR: Too long' : 'Acceptable' ) . "\n";
	// Commented out per https://github.com/easydigitaldownloads/Easy-Digital-Downloads/issues/3475
	//$return .= 'Admin AJAX:               ' . ( groundhogg_test_ajax_works() ? 'Accessible' : 'Inaccessible' ) . "\n";
	$return .= 'WP_DEBUG:                 ' . ( defined( 'WP_DEBUG' ) ? WP_DEBUG ? 'Enabled' : 'Disabled' : 'Not set' ) . "\n";
	$return .= 'Memory Limit:             ' . WP_MEMORY_LIMIT . "\n";
	$return .= 'Registered Post Stati:    ' . implode( ', ', get_post_stati() ) . "\n";

	$return = apply_filters( 'groundhogg_sysinfo_after_wordpress_config', $return );

	// Groundhogg configuration
	$return .= "\n" . '-- Plugin Configuration' . "\n\n";
	$return .= 'Version:                  ' . GROUNDHOGG_VERSION . "\n";
	maybe_install_safe_mode_plugin();// for safe mode
	$return .= 'Safe Mode:                ' . ( groundhogg_is_safe_mode_enabled() ? "Enabled\n" : "Disabled\n" );
	$last_used = get_option( 'gh_safe_mode_last_used' );
	$last_used = $last_used ? (new DateTimeHelper($last_used))->human_time_diff() : 'Never';
	$return .= 'Safe Mode Last Used:      ' . $last_used . ' ago';

	$return = apply_filters( 'groundhogg_sysinfo_after_plugin_config', $return );

	$return .= "\n" . '-- Compliance Configuration' . "\n\n";
	$return .= 'Confirmation Required:    ' . ( $plugin->preferences->is_confirmation_strict() ? "Enabled\n" : "Disabled\n" );
	$return .= 'Grace Period:             ' . $plugin->preferences->get_grace_period() . "\n";
	$return .= 'GDPR Enabled:             ' . ( ( $plugin->preferences->is_gdpr_enabled() ) ? "Enabled\n" : "Disabled\n" );
	$return .= 'GDPR Strict:              ' . ( $plugin->preferences->is_gdpr_strict() ? "Enabled\n" : "Disabled\n" );
	$return = apply_filters( 'groundhogg_sysinfo_after_compliance_config', $return );

	$return .= "\n" . '-- Bounce Configuration' . "\n\n";

	$return .= 'IMAP Enabled:             ' . ( function_exists( 'imap_open' ) ? "Enabled\n" : "Disabled\n" );
	$return .= 'Tracking Bounces:         ' . ( $plugin->bounce_checker->get_bounce_inbox() && $plugin->bounce_checker->get_bounce_inbox_pw() ? "Enabled\n" : "Disabled\n" );
	$return .= 'Mail Server:              ' . $plugin->bounce_checker->get_mail_server() . "\n";
	$return .= 'Port:                     ' . $plugin->bounce_checker->get_port() . "\n";
	$return = apply_filters( 'groundhogg_sysinfo_after_bounce_config', $return );

	$return .= "\n" . '-- Email Configuration' . "\n\n";
	$return .= 'wp_mail() defined in:     ' . extrapolate_wp_mail_plugin() . "\n";
	$return .= 'Default FROM:             ' . sprintf( "%s <%s>", get_default_from_name(), \Groundhogg\get_default_from_email() ) . "\n";
	$return .= 'WordPress Service:        ' . Groundhogg_Email_Services::get_service_display_name( Groundhogg_Email_Services::get_wordpress_service() ) . "\n";
	$return .= 'Transactional Service:    ' . Groundhogg_Email_Services::get_service_display_name( Groundhogg_Email_Services::get_transactional_service() ) . "\n";
	$return .= 'Marketing Service:        ' . Groundhogg_Email_Services::get_service_display_name( Groundhogg_Email_Services::get_marketing_service() ) . "\n";
	$return = apply_filters( 'groundhogg_sysinfo_after_email_config', $return );

	$return .= "\n" . '-- CRON Configuration' . "\n\n";
	$return .= 'DISABLE_WP_CRON:          ' . ( defined( 'DISABLE_WP_CRON' ) ? DISABLE_WP_CRON ? 'Enabled' : 'Disabled' : 'Not set' ) . "\n";
	$return .= 'wp-cron.php last ping:    ' . ( get_option( 'wp_cron_last_ping' ) ? human_time_diff( get_option( 'wp_cron_last_ping' ), time() ) . ' ago' : 'Not pinged yet...' ) . "\n\n";
	$return .= 'gh-cron.php installed:    ' . ( gh_cron_installed() ? 'Yes' : 'No' ) . "\n";
	$return .= 'Event Queue Unhooked:     ' . ( ! wp_next_scheduled( Event_Queue::WP_CRON_HOOK ) ? 'Yes' : 'No' ) . "\n";
	$return .= 'gh-cron.php last ping:    ' . ( get_option( 'gh_cron_last_ping' ) ? human_time_diff( get_option( 'gh_cron_last_ping' ), time() ) . ' ago' : 'Not pinged yet...' ) . "\n";

	$return = apply_filters( 'groundhogg_sysinfo_after_cron_config', $return );

	// Groundhogg Templates
	$dir = get_stylesheet_directory() . '/groundhogg-templates/*';
	if ( is_dir( $dir ) && ( count( glob( "$dir/*" ) ) !== 0 ) ) {
		$return .= "\n" . '-- Groundhogg Template Overrides' . "\n\n";

		foreach ( glob( $dir ) as $file ) {
			$return .= 'Filename:          ' . basename( $file ) . "\n";
		}

		$return = apply_filters( 'groundhogg_sysinfo_after_groundhogg_templates', $return );
	}

	// Server configuration (really just versioning)
	$return .= "\n" . '-- Webserver Configuration' . "\n\n";
	$return .= 'PHP Version:              ' . PHP_VERSION . "\n";
	$return .= 'MySQL Version:            ' . $wpdb->db_version() . "\n";
	$return .= 'Webserver Info:           ' . $_SERVER['SERVER_SOFTWARE'] . "\n";

	$return = apply_filters( 'groundhogg_sysinfo_after_webserver_config', $return );

	$return .= "\n" . '-- MySQL Configuration' . "\n\n";
	$return .= 'MySQL Version:        ' . $wpdb->db_version() . "\n";
	$return .= 'Engines:              ' . implode( ',', wp_list_pluck( $wpdb->get_results( 'SHOW ENGINES;' ), 'Engine' ) ) . "\n";
	$return .= 'Collation:            ' . $wpdb->collate . "\n";
	$return .= 'Charset:              ' . $wpdb->charset . "\n";

	// PHP configs... now we're getting to the important stuff
	$return .= "\n" . '-- PHP Configuration' . "\n\n";
	$return .= 'PHP Version:              ' . PHP_VERSION . "\n";
	$return .= 'Memory Limit:             ' . ini_get( 'memory_limit' ) . "\n";
	$return .= 'Upload Max Size:          ' . ini_get( 'upload_max_filesize' ) . "\n";
	$return .= 'Post Max Size:            ' . ini_get( 'post_max_size' ) . "\n";
	$return .= 'Upload Max Filesize:      ' . ini_get( 'upload_max_filesize' ) . "\n";
	$return .= 'Time Limit:               ' . ini_get( 'max_execution_time' ) . "\n";
	$return .= 'Max Input Vars:           ' . ini_get( 'max_input_vars' ) . "\n";
	$return .= 'Display Errors:           ' . ( ini_get( 'display_errors' ) ? 'On (' . ini_get( 'display_errors' ) . ')' : 'N/A' ) . "\n";
	$return .= 'PHP Arg Separator:        ' . ini_get( 'arg_separator.output' ) . "\n";

	$return = apply_filters( 'groundhogg_sysinfo_after_php_config', $return );

	// PHP extensions and such
	$return .= "\n" . '-- PHP Extensions' . "\n\n";
	$return .= 'cURL:                     ' . ( function_exists( 'curl_init' ) ? 'Supported' : 'Not Supported' ) . "\n";
	$return .= 'fsockopen:                ' . ( function_exists( 'fsockopen' ) ? 'Supported' : 'Not Supported' ) . "\n";
	$return .= 'SOAP Client:              ' . ( class_exists( 'SoapClient' ) ? 'Installed' : 'Not Installed' ) . "\n";
	$return .= 'Suhosin:                  ' . ( extension_loaded( 'suhosin' ) ? 'Installed' : 'Not Installed' ) . "\n";

	$return = apply_filters( 'groundhogg_sysinfo_after_php_ext', $return );

	// Session stuff
	$return .= "\n" . '-- Session Configuration' . "\n\n";
	$return .= 'Session:                  ' . ( isset( $_SESSION ) ? 'Enabled' : 'Disabled' ) . "\n";

	// The rest of this is only relevant is session is enabled
	if ( isset( $_SESSION ) ) {
		$return .= 'Session Name:             ' . esc_html( ini_get( 'session.name' ) ) . "\n";
		$return .= 'Cookie Path:              ' . esc_html( ini_get( 'session.cookie_path' ) ) . "\n";
		$return .= 'Save Path:                ' . esc_html( ini_get( 'session.save_path' ) ) . "\n";
		$return .= 'Use Cookies:              ' . ( ini_get( 'session.use_cookies' ) ? 'On' : 'Off' ) . "\n";
		$return .= 'Use Only Cookies:         ' . ( ini_get( 'session.use_only_cookies' ) ? 'On' : 'Off' ) . "\n";
	}

	$return = apply_filters( 'groundhogg_sysinfo_after_session_config', $return );

	$plugin = \Groundhogg\Plugin::instance();

	$return .= "\n" . '-- Tables' . "\n\n";

	$dbs = $plugin->dbs->get_dbs();

	foreach ( $dbs as $db ) {
		$return .= str_pad( sprintf( '%s:', $db->get_table_name() ), 25, ' ', STR_PAD_RIGHT ) . ( $db->installed() ? 'Installed' : 'Not Installed' ) . "\n";
	}

	$return = apply_filters( 'groundhogg_sysinfo_after_tables', $return );


	// Get plugins that have an update
	$updates = get_plugin_updates();

	// Must-use plugins
	// NOTE: MU plugins can't show updates!
	$muplugins = get_mu_plugins();
	if ( count( $muplugins ) > 0 ) {
		$return .= "\n" . '-- Must-Use Plugins' . "\n\n";

		foreach ( $muplugins as $plugin => $plugin_data ) {
			$return .= $plugin_data['Name'] . ': ' . $plugin_data['Version'] . "\n";
		}

		$return = apply_filters( 'groundhogg_sysinfo_after_wordpress_mu_plugins', $return );
	}

	// WordPress active plugins
	$return .= "\n" . '-- WordPress Active Plugins' . "\n\n";

	$plugins        = get_plugins();
	$active_plugins = get_option( 'active_plugins', array() );

	foreach ( $plugins as $plugin_path => $plugin ) {
		if ( ! in_array( $plugin_path, $active_plugins ) ) {
			continue;
		}

		$update = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[ $plugin_path ]->update->new_version . ')' : '';
		$return .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
	}

	$return = apply_filters( 'groundhogg_sysinfo_after_wordpress_plugins', $return );

	// WordPress inactive plugins
	$return .= "\n" . '-- WordPress Inactive Plugins' . "\n\n";

	foreach ( $plugins as $plugin_path => $plugin ) {
		if ( in_array( $plugin_path, $active_plugins ) ) {
			continue;
		}

		$update = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[ $plugin_path ]->update->new_version . ')' : '';
		$return .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
	}

	$return = apply_filters( 'groundhogg_sysinfo_after_wordpress_plugins_inactive', $return );

	if ( is_multisite() ) {
		// WordPress Multisite active plugins
		$return .= "\n" . '-- Network Active Plugins' . "\n\n";

		$plugins        = wp_get_active_network_plugins();
		$active_plugins = get_site_option( 'active_sitewide_plugins', array() );

		foreach ( $plugins as $plugin_path ) {
			$plugin_base = plugin_basename( $plugin_path );

			if ( ! array_key_exists( $plugin_base, $active_plugins ) ) {
				continue;
			}

			$update = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[ $plugin_path ]->update->new_version . ')' : '';
			$plugin = get_plugin_data( $plugin_path );
			$return .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
		}

		$return = apply_filters( 'groundhogg_sysinfo_after_wordpress_ms_plugins', $return );
	}

	$return .= "\n" . '### End System Info ###';

	return $return;
}

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the Nameserver records for the host.
 *
 * @return array
 */
function groundhogg_get_ns_records() {
	$ns = @dns_get_record( str_replace( 'www.', '', $_SERVER['hostname'] ), DNS_NS );

	return wp_list_pluck( $ns, 'target' );
}

/**
 * Get user host
 *
 * Returns the webhost this site is using if possible
 *
 * @since 1.0.9
 * @return mixed string $host if detected, false otherwise
 */
function groundhogg_get_host() {

	if ( defined( 'WPE_APIKEY' ) ) {
		$host = 'WP Engine';
	} else if ( defined( 'PAGELYBIN' ) ) {
		$host = 'Pagely';
	} else if ( DB_HOST == 'localhost:/tmp/mysql5.sock' ) {
		$host = 'ICDSoft';
	} else if ( DB_HOST == 'mysqlv5' ) {
		$host = 'NetworkSolutions';
	} else if ( strpos( DB_HOST, 'ipagemysql.com' ) !== false ) {
		$host = 'iPage';
	} else if ( strpos( DB_HOST, 'ipowermysql.com' ) !== false ) {
		$host = 'IPower';
	} else if ( strpos( DB_HOST, '.gridserver.com' ) !== false ) {
		$host = 'MediaTemple Grid';
	} else if ( strpos( DB_HOST, '.pair.com' ) !== false ) {
		$host = 'pair Networks';
	} else if ( strpos( DB_HOST, '.stabletransit.com' ) !== false ) {
		$host = 'Rackspace Cloud';
	} else if ( strpos( DB_HOST, '.sysfix.eu' ) !== false ) {
		$host = 'SysFix.eu Power Hosting';
	} else if ( strpos( $_SERVER['SERVER_NAME'], 'Flywheel' ) !== false ) {
		$host = 'Flywheel';
	} else if ( defined( 'SiteGround_Optimizer\VERSION' ) ) {
		$host = 'SiteGround';
	} else if ( defined( 'CLOSTE_DIR' ) ) {
		$host = 'Closte';
	} else {
		// Adding a general fallback for data gathering
		$host = 'DBH: ' . DB_HOST . ', SRV: ' . $_SERVER['SERVER_NAME'];
	}

	return $host;
}

/**
 * Generates a System Info download file
 *
 * @since       2.0
 * @return      void
 */
function groundhogg_tools_sysinfo_download() {

	if ( ! is_admin() ) {
		return;
	}

	if ( ! isset( $_REQUEST['gh_download_sys_info'] ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	nocache_headers();

	header( 'Content-Type: text/plain' );
	header( 'Content-Disposition: attachment; filename="groundhogg-system-info.txt"' );

	echo wp_strip_all_tags( groundhogg_tools_sysinfo_get() );
	die();
}

add_action( 'admin_init', 'groundhogg_tools_sysinfo_download' );

/**
 * Make sure that the safe mode plugin is installed
 *
 * @return void
 */
function maybe_install_safe_mode_plugin() {

	// safe mode plugin is installed in the mu plugins dir
	if ( defined( 'GROUNDHOGG_SAFE_MODE_INSTALLED' ) ) {
		return;
	}

	$mu_plugins_dir = WPMU_PLUGIN_DIR;

	// Ensure the mu-plugins directory exists
	if ( ! is_dir( $mu_plugins_dir ) ) {
		mkdir( $mu_plugins_dir, 0755, true );
	}

	$source      = __DIR__ . '/../mu-plugins/safe-mode.php';
	$destination = $mu_plugins_dir . '/groundhogg-safe-mode.php';

	if ( file_exists( $source ) ) {
		copy( $source, $destination );
	}

	// set the cookie secret
	\Groundhogg\search_and_replace_in_file( $destination, 'REPLACEWITHKEY', wp_generate_password( 20 ) );

	include_once $destination;
}
