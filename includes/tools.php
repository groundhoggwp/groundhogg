<?php
/**
 * Tools
 *
 * @package     Includes
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 1.0.9
 */

/**
 * Output content for the tools
 */
function wpgh_tools_page()
{
    ?>
    <div id="poststuff">
        <?php do_action('gh_settings_tools'); ?>
    </div>
    <?php
}

add_action( 'gh_tab_tools', 'wpgh_tools_page' );

/**
 * Display the bulk import tool
 *
 * @since       1.0.9
 * @return      void
 */
function wpgh_import_contacts_display()
{
    ?>
    <!-- Begin Import Tool -->
    <div class="postbox">
        <h2 class="hndle"><?php _e( 'Import Contacts', 'groundhogg' ); ?></h2>
        <div class="inside">
            <p>
                <input type="file" id="contacts" name="contacts" accept=".csv" >
            </p>
            <p class="description"><a target="_blank" href="https://docs.groundhogg.io/docs/settings/getting-started/import-your-list/"><?php _e( "Learn how to import contacts.", 'groundhogg' ); ?></a></p>
            <hr>
            <?php $tag_args = array();
            $tag_args[ 'id' ] = 'import_tags';
            $tag_args[ 'name' ] = 'import_tags[]'; ?>
            <?php echo WPGH()->html->tag_picker( $tag_args ); ?>
            <div class="import-status-wrapper"><p><strong><span class="import-status"></span></strong></p></div>
            <p class="description"><?php _e( 'These tags will be applied to the contacts upon importing.', 'groundhogg' ); ?></p>
            <p class="submit">
                <button class="import button button-primary" id="import" type="button"><?php _e( 'Import Contacts' ); ?></button>
                <span class="spinner spinner-import" style="float: none"></span>
            </p>
        </div>
    </div>
    <!-- End Import Tool -->
    <?php
}

add_action( 'gh_settings_tools', 'wpgh_import_contacts_display' );

/**
 * Display the bulk export tool
 *
 * @since       1.0.9
 * @return      void
 */
function wpgh_export_contacts_display()
{
    ?>
    <!-- Begin Export Tool -->
    <div class="postbox">
        <h2 class="hndle"><?php _e( 'Export Contacts', 'groundhogg' ); ?></h2>
        <div class="inside">
            <p class="description"><?php _e( 'Export contacts to a .CSV file. This will download to your browser.', 'groundhogg' ); ?></p>
            <?php $tag_args = array();
            $tag_args[ 'id' ] = 'export_tags';
            $tag_args[ 'name' ] = 'export_tags[]';?>
            <?php echo WPGH()->html->tag_picker( $tag_args ); ?>
            <p class="description"><?php _e( 'Contacts with these tags will be exported. Leave blank to export ALL contacts.', 'groundhogg' ); ?></p>
            <p class="submit">
                <button class="export button button-primary" id="export" type="button"><?php _e( 'Export Contacts' ); ?></button>
                <span class="spinner spinner-export" style="float: none"></span>
            </p>
        </div>
    </div>
    <!-- End Export Tool -->

    <?php
}

add_action( 'gh_settings_tools', 'wpgh_export_contacts_display' );

/**
 * Display the bulk delete tool
 *
 * @since       1.0.9
 * @return      void
 */
function wpgh_bulk_delete_contacts_display()
{
    ?>
    <!-- Begin Delete Tool -->
    <div class="postbox">
        <h2 class="hndle"><?php _e( 'Bulk Delete Contacts', 'groundhogg' ); ?></h2>
        <div class="inside">
            <p class="description"><?php _e( 'Bulk delete contacts.', 'groundhogg' ); ?></p>
            <?php $tag_args = array();
            $tag_args[ 'id' ] = 'delete_tags';
            $tag_args[ 'name' ] = 'delete_tags[]';?>
            <?php echo WPGH()->html->tag_picker( $tag_args ); ?>
            <p class="description"><?php _e( 'Contacts with these tags will be delete. Leave blank to delete ALL contacts.', 'groundhogg' ); ?></p>
            <p class="submit">
                <button class="bulk-delete button button-primary" id="delete" type="button"><?php _e( 'Delete Contacts' ); ?></button>
                <span class="spinner spinner-delete" style="float:none;"></span>
            </p>
        </div>
    </div>
    <!-- End Delete Tool -->
    <?php
}

add_action( 'gh_settings_tools', 'wpgh_bulk_delete_contacts_display' );

/**
 * Display the system info tab
 *
 * @since       2.0
 * @return      void
 */
function wpgh_tools_sysinfo_display() {

    if( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    ?>
    <!-- Begin Delete Tool -->
    <div class="postbox">
        <h2 class="hndle"><?php _e( 'Download System Info', 'groundhogg' ); ?></h2>
        <div class="inside">
            <p class="description"><?php _e( 'Download System Info when requesting support.', 'groundhogg' ); ?></p>

            <textarea class="code" style="width: 100%;height:600px;" readonly="readonly" onclick="this.focus(); this.select()" id="system-info-textarea" name="wpgh-sysinfo"><?php echo wpgh_tools_sysinfo_get(); ?></textarea>
            <p class="submit">
                <a class="button button-primary" href="<?php echo admin_url( '?gh_download_sys_info=1' ) ?>"><?php _e( 'Download System Info', 'groundhogg' ); ?></a>
            </p>

        </div>
    </div>

    <?php
}

add_action( 'gh_settings_tools', 'wpgh_tools_sysinfo_display' );


/**
 * Get system info
 *
 * @since       2.0
 * @global      object $wpdb Used to query the database using the WordPress Database API
 * @return      string $return A string containing the info to output
 */
function wpgh_tools_sysinfo_get() {
    global $wpdb;

    if( !class_exists( 'Browser' ) )
        require_once WPGH_PLUGIN_DIR . 'includes/lib/browser.php';

    $browser = new Browser();

    // Get theme info
    $theme_data   = wp_get_theme();
    $theme        = $theme_data->Name . ' ' . $theme_data->Version;
    $parent_theme = $theme_data->Template;
    if ( ! empty( $parent_theme ) ) {
        $parent_theme_data = wp_get_theme( $parent_theme );
        $parent_theme      = $parent_theme_data->Name . ' ' . $parent_theme_data->Version;
    }

    // Try to identify the hosting provider
    $host = wpgh_get_host();

    $return  = '### Begin System Info ###' . "\n\n";

    // Start with the basics...
    $return .= '-- Site Info' . "\n\n";
    $return .= 'Site URL:                 ' . site_url() . "\n";
    $return .= 'Home URL:                 ' . home_url() . "\n";
    $return .= 'Multisite:                ' . ( is_multisite() ? 'Yes' : 'No' ) . "\n";

    $return  = apply_filters( 'wpgh_sysinfo_after_site_info', $return );

    // Can we determine the site's host?
    if( $host ) {
        $return .= "\n" . '-- Hosting Provider' . "\n\n";
        $return .= 'Host:                     ' . $host . "\n";

        $return  = apply_filters( 'wpgh_sysinfo_after_host_info', $return );
    }

    // The local users' browser information, handled by the Browser class
    $return .= "\n" . '-- User Browser' . "\n\n";
    $return .= $browser;

    $return  = apply_filters( 'wpgh_sysinfo_after_user_browser', $return );

    $locale = get_locale();

    // WordPress configuration
    $return .= "\n" . '-- WordPress Configuration' . "\n\n";
    $return .= 'Version:                  ' . get_bloginfo( 'version' ) . "\n";
    $return .= 'Language:                 ' . ( !empty( $locale ) ? $locale : 'en_US' ) . "\n";
    $return .= 'Permalink Structure:      ' . ( get_option( 'permalink_structure' ) ? get_option( 'permalink_structure' ) : 'Default' ) . "\n";
    $return .= 'Active Theme:             ' . $theme . "\n";
    if ( $parent_theme !== $theme ) {
        $return .= 'Parent Theme:             ' . $parent_theme . "\n";
    }
    $return .= 'Show On Front:            ' . get_option( 'show_on_front' ) . "\n";

    // Only show page specs if frontpage is set to 'page'
    if( get_option( 'show_on_front' ) == 'page' ) {
        $front_page_id = get_option( 'page_on_front' );
        $blog_page_id = get_option( 'page_for_posts' );

        $return .= 'Page On Front:            ' . ( $front_page_id != 0 ? get_the_title( $front_page_id ) . ' (#' . $front_page_id . ')' : 'Unset' ) . "\n";
        $return .= 'Page For Posts:           ' . ( $blog_page_id != 0 ? get_the_title( $blog_page_id ) . ' (#' . $blog_page_id . ')' : 'Unset' ) . "\n";
    }

    $return .= 'ABSPATH:                  ' . ABSPATH . "\n";

    // Make sure wp_remote_post() is working
    $request['cmd'] = '_notify-validate';

    $params = array(
        'sslverify'     => false,
        'timeout'       => 60,
        'user-agent'    => 'WPGH/' . WPGH()->version,
        'body'          => $request
    );

    $response = wp_remote_post( 'https://www.groundhogg.io', $params );

    if( !is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
        $WP_REMOTE_POST = 'wp_remote_post() works';
    } else {
        $WP_REMOTE_POST = 'wp_remote_post() does not work';
    }

    $return .= 'Remote Post:              ' . $WP_REMOTE_POST . "\n";
    $return .= 'Table Prefix:             ' . 'Length: ' . strlen( $wpdb->prefix ) . '   Status: ' . ( strlen( $wpdb->prefix ) > 16 ? 'ERROR: Too long' : 'Acceptable' ) . "\n";
    // Commented out per https://github.com/easydigitaldownloads/Easy-Digital-Downloads/issues/3475
    //$return .= 'Admin AJAX:               ' . ( wpgh_test_ajax_works() ? 'Accessible' : 'Inaccessible' ) . "\n";
    $return .= 'WP_DEBUG:                 ' . ( defined( 'WP_DEBUG' ) ? WP_DEBUG ? 'Enabled' : 'Disabled' : 'Not set' ) . "\n";
    $return .= 'Memory Limit:             ' . WP_MEMORY_LIMIT . "\n";
    $return .= 'Registered Post Stati:    ' . implode( ', ', get_post_stati() ) . "\n";

    $return  = apply_filters( 'wpgh_sysinfo_after_wordpress_config', $return );

    // Groundhogg configuration
    $return .= "\n" . '-- Plugin Configuration' . "\n\n";
    $return .= 'Version:                  ' . WPGH()->version . "\n";
    $return .= 'Global Multisite:         ' . ( wpgh_is_global_multisite() ? "Enabled\n" : "Disabled\n" );
    $return .= 'ReCaptcha:                ' . ( wpgh_is_recaptcha_enabled() ? "Enabled\n" : "Disabled\n" );
    $return .= 'Confirmed Only:           ' . ( wpgh_is_confirmation_strict() ? "Enabled\n" : "Disabled\n" );
    $return .= 'Confirmation G.P.:        ' . intval( wpgh_get_option( 'gh_confirmation_grace_period', 14 ) ) . "\n";
    $return .= 'GDPR Enabled:             ' . ( wpgh_is_gdpr() ? "Enabled\n" : "Disabled\n" );
    $return .= 'GDPR Strict:              ' . ( wpgh_is_gdpr_strict() ? "Enabled\n" : "Disabled\n" );
    $return .= 'Tracking Bounces:         ' . ( wpgh_get_option( 'gh_bounce_inbox' ) && wpgh_get_option( 'gh_bounce_inbox_password' ) ? "Enabled\n" : "Disabled\n" );
    $return .= 'Email Service:            ' . ( wpgh_get_option( 'gh_email_token' ) && wpgh_is_email_api_enabled() ? "Enabled\n" : "Disabled\n" );

    $return  = apply_filters( 'wpgh_sysinfo_after_wpgh_config', $return );

    // Groundhogg pages
    $confirmation_page          = wpgh_get_option( 'gh_email_confirmation_page', '' );
    $unsubscribe_page           = wpgh_get_option( 'gh_unsubscribe_page', '' );
    $email_preferences_page     = wpgh_get_option( 'gh_email_preferences_page', '' );

    $return .= "\n" . '-- Page Configuration' . "\n\n";
    $return .= 'Confirmation Page:            ' . ( !empty( $confirmation_page ) ? get_permalink( $confirmation_page ) . "\n" : "Unset\n" );
    $return .= 'unsubscribed Page:            ' . ( !empty( $unsubscribe_page ) ? get_permalink( $unsubscribe_page ) . "\n" : "Unset\n" );
    $return .= 'Email Preferences Page:       ' . ( !empty( $email_preferences_page ) ? get_permalink( $email_preferences_page ) . "\n" : "Unset\n" );

    $return  = apply_filters( 'wpgh_sysinfo_after_wpgh_pages', $return );

    // WPGH Templates
    $dir = get_stylesheet_directory() . '/wpgh_templates/*';
    if( is_dir( $dir ) && ( count( glob( "$dir/*" ) ) !== 0 ) ) {
        $return .= "\n" . '-- WPGH Template Overrides' . "\n\n";

        foreach( glob( $dir ) as $file ) {
            $return .= 'Filename:                 ' . basename( $file ) . "\n";
        }

        $return  = apply_filters( 'wpgh_sysinfo_after_wpgh_templates', $return );
    }

    // Get plugins that have an update
    $updates = get_plugin_updates();

    // Must-use plugins
    // NOTE: MU plugins can't show updates!
    $muplugins = get_mu_plugins();
    if( count( $muplugins ) > 0 ) {
        $return .= "\n" . '-- Must-Use Plugins' . "\n\n";

        foreach( $muplugins as $plugin => $plugin_data ) {
            $return .= $plugin_data['Name'] . ': ' . $plugin_data['Version'] . "\n";
        }

        $return = apply_filters( 'wpgh_sysinfo_after_wordpress_mu_plugins', $return );
    }

    // WordPress active plugins
    $return .= "\n" . '-- WordPress Active Plugins' . "\n\n";

    $plugins = get_plugins();
    $active_plugins = get_option( 'active_plugins', array() );

    foreach( $plugins as $plugin_path => $plugin ) {
        if( !in_array( $plugin_path, $active_plugins ) )
            continue;

        $update = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[$plugin_path]->update->new_version . ')' : '';
        $return .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
    }

    $return  = apply_filters( 'wpgh_sysinfo_after_wordpress_plugins', $return );

    // WordPress inactive plugins
    $return .= "\n" . '-- WordPress Inactive Plugins' . "\n\n";

    foreach( $plugins as $plugin_path => $plugin ) {
        if( in_array( $plugin_path, $active_plugins ) )
            continue;

        $update = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[$plugin_path]->update->new_version . ')' : '';
        $return .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
    }

    $return  = apply_filters( 'wpgh_sysinfo_after_wordpress_plugins_inactive', $return );

    if( is_multisite() ) {
        // WordPress Multisite active plugins
        $return .= "\n" . '-- Network Active Plugins' . "\n\n";

        $plugins = wp_get_active_network_plugins();
        $active_plugins = get_site_option( 'active_sitewide_plugins', array() );

        foreach( $plugins as $plugin_path ) {
            $plugin_base = plugin_basename( $plugin_path );

            if( !array_key_exists( $plugin_base, $active_plugins ) )
                continue;

            $update = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[$plugin_path]->update->new_version . ')' : '';
            $plugin  = get_plugin_data( $plugin_path );
            $return .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
        }

        $return  = apply_filters( 'wpgh_sysinfo_after_wordpress_ms_plugins', $return );
    }

    // Server configuration (really just versioning)
    $return .= "\n" . '-- Webserver Configuration' . "\n\n";
    $return .= 'PHP Version:              ' . PHP_VERSION . "\n";
    $return .= 'MySQL Version:            ' . $wpdb->db_version() . "\n";
    $return .= 'Webserver Info:           ' . $_SERVER['SERVER_SOFTWARE'] . "\n";

    $return  = apply_filters( 'wpgh_sysinfo_after_webserver_config', $return );

    // PHP configs... now we're getting to the important stuff
    $return .= "\n" . '-- PHP Configuration' . "\n\n";
    $return .= 'Memory Limit:             ' . ini_get( 'memory_limit' ) . "\n";
    $return .= 'Upload Max Size:          ' . ini_get( 'upload_max_filesize' ) . "\n";
    $return .= 'Post Max Size:            ' . ini_get( 'post_max_size' ) . "\n";
    $return .= 'Upload Max Filesize:      ' . ini_get( 'upload_max_filesize' ) . "\n";
    $return .= 'Time Limit:               ' . ini_get( 'max_execution_time' ) . "\n";
    $return .= 'Max Input Vars:           ' . ini_get( 'max_input_vars' ) . "\n";
    $return .= 'Display Errors:           ' . ( ini_get( 'display_errors' ) ? 'On (' . ini_get( 'display_errors' ) . ')' : 'N/A' ) . "\n";
    $return .= 'PHP Arg Separator:        ' . ini_get( 'arg_separator.output' ) . "\n";

    $return  = apply_filters( 'wpgh_sysinfo_after_php_config', $return );

    // PHP extensions and such
    $return .= "\n" . '-- PHP Extensions' . "\n\n";
    $return .= 'cURL:                     ' . ( function_exists( 'curl_init' ) ? 'Supported' : 'Not Supported' ) . "\n";
    $return .= 'fsockopen:                ' . ( function_exists( 'fsockopen' ) ? 'Supported' : 'Not Supported' ) . "\n";
    $return .= 'SOAP Client:              ' . ( class_exists( 'SoapClient' ) ? 'Installed' : 'Not Installed' ) . "\n";
    $return .= 'Suhosin:                  ' . ( extension_loaded( 'suhosin' ) ? 'Installed' : 'Not Installed' ) . "\n";

    $return  = apply_filters( 'wpgh_sysinfo_after_php_ext', $return );

    // Session stuff
    $return .= "\n" . '-- Session Configuration' . "\n\n";
    $return .= 'Session:                  ' . ( isset( $_SESSION ) ? 'Enabled' : 'Disabled' ) . "\n";

    // The rest of this is only relevant is session is enabled
    if( isset( $_SESSION ) ) {
        $return .= 'Session Name:             ' . esc_html( ini_get( 'session.name' ) ) . "\n";
        $return .= 'Cookie Path:              ' . esc_html( ini_get( 'session.cookie_path' ) ) . "\n";
        $return .= 'Save Path:                ' . esc_html( ini_get( 'session.save_path' ) ) . "\n";
        $return .= 'Use Cookies:              ' . ( ini_get( 'session.use_cookies' ) ? 'On' : 'Off' ) . "\n";
        $return .= 'Use Only Cookies:         ' . ( ini_get( 'session.use_only_cookies' ) ? 'On' : 'Off' ) . "\n";
    }

    $return .= "\n" . '-- Event Queue information' . "\n\n";
    $return .= 'Average Execution Time:       ' . esc_html( wpgh_get_option( 'gh_average_execution_time' ) ) . " seconds\n";
    $return .= 'Last Execution Time:          ' . esc_html( wpgh_get_option( 'gh_queue_last_execution_time' ) ) . " seconds\n";
    $return .= 'Max Execution Time:           ' . esc_html( WPGH()->event_queue->get_max_execution_time() ) . " seconds\n";
    $return .= 'Total Executions:             ' . esc_html( wpgh_get_option( 'gh_queue_times_executed' ) ) . "\n";

    $return  = apply_filters( 'wpgh_sysinfo_after_session_config', $return );

    $return .= "\n" . '### End System Info ###';

    return $return;
}


/**
 * Get user host
 *
 * Returns the webhost this site is using if possible
 *
 * @since 1.0.9
 * @return mixed string $host if detected, false otherwise
 */
function wpgh_get_host() {
    $host = false;

    if( defined( 'WPE_APIKEY' ) ) {
        $host = 'WP Engine';
    } elseif( defined( 'PAGELYBIN' ) ) {
        $host = 'Pagely';
    } elseif( DB_HOST == 'localhost:/tmp/mysql5.sock' ) {
        $host = 'ICDSoft';
    } elseif( DB_HOST == 'mysqlv5' ) {
        $host = 'NetworkSolutions';
    } elseif( strpos( DB_HOST, 'ipagemysql.com' ) !== false ) {
        $host = 'iPage';
    } elseif( strpos( DB_HOST, 'ipowermysql.com' ) !== false ) {
        $host = 'IPower';
    } elseif( strpos( DB_HOST, '.gridserver.com' ) !== false ) {
        $host = 'MediaTemple Grid';
    } elseif( strpos( DB_HOST, '.pair.com' ) !== false ) {
        $host = 'pair Networks';
    } elseif( strpos( DB_HOST, '.stabletransit.com' ) !== false ) {
        $host = 'Rackspace Cloud';
    } elseif( strpos( DB_HOST, '.sysfix.eu' ) !== false ) {
        $host = 'SysFix.eu Power Hosting';
    } elseif( strpos( $_SERVER['SERVER_NAME'], 'Flywheel' ) !== false ) {
        $host = 'Flywheel';
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
function wpgh_tools_sysinfo_download() {

    if ( ! is_admin() )
        return;

    if ( ! isset( $_REQUEST[ 'gh_download_sys_info' ] ) ){
        return;
    }

    if( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    nocache_headers();

    header( 'Content-Type: text/plain' );
    header( 'Content-Disposition: attachment; filename="wpgh-system-info.txt"' );

    echo wp_strip_all_tags( wpgh_tools_sysinfo_get() );
    die();
}

add_action( 'admin_init', 'wpgh_tools_sysinfo_download' );

/**
 * Show the api keys table
 */
function wpgh_api_keys_table()
{
    if ( ! wpgh_is_option_enabled( 'gh_enable_api' ) ){
        return;
    }

    ?>
    </form>
    <?php


    do_action( 'wpgh_tools_api_keys_before' );

    require_once WPGH_PLUGIN_DIR . 'includes/admin/settings/class-wpgh-api-keys-table.php';

    $api_keys_table = new WPGH_API_Keys_Table();
    $api_keys_table->prepare_items();
    $api_keys_table->display();
    ?>
    <p>
        <?php _e( 'These API keys allow you to use the REST API to retrieve store data in JSON for external applications or devices.', 'groundhogg' ); ?>
    </p>
    <form>
    <?php

    do_action( 'wpgh_tools_api_keys_after' );


}

add_action( 'gh_tab_api_tab', 'wpgh_api_keys_table' );
