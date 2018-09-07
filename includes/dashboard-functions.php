<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-09-07
 * Time: 8:25 AM
 */


function wpgh_add_dashboard_widgets() {

    wp_add_dashboard_widget(
        'wpgh_at_a_glance',         // Widget slug.
        'Groundhogg Stats in the Last 30 Days',         // Title.
        'wpgh_at_a_glance_report' // Display function.
    );
}

add_action( 'wp_dashboard_setup', 'wpgh_add_dashboard_widgets' );

/**
 * Create the function to output the contents of our Dashboard Widget.
 */
function wpgh_at_a_glance_report() {

    global $wpdb;

    wp_enqueue_style( 'wpgh-dashboard', WPGH_ASSETS_FOLDER . '/css/admin/dashboard.css' );

    /* New Contacts */
    ?><div class="wpgh-dashboard-widget"><table class="stats"><?php

    $thirty_days_ago = current_time( 'timestamp' ) - 30 * DAY_IN_SECONDS;

    $date = date( 'Y-m-d H:i:s', $thirty_days_ago );

    $table = $wpdb->prefix . WPGH_CONTACTS;

    $new_contacts = $wpdb->get_var( $wpdb->prepare( "SELECT count(*) FROM $table WHERE date_created >= %s", $date ) );

    ?>
    <tr>
        <td>
            <span class="alignleft"><?php _e( 'New Contacts' ); ?></span>
        </td>
        <th>
            <span class="alignright"><?php echo $new_contacts; ?></span>
        </th>
    </tr>
    <?php

    /* Emails Sent */
    $table = $wpdb->prefix . WPGH_EVENTS;
    $steps = $wpdb->prefix . WPGH_FUNNELSTEPS;

    $emails_sent = count($wpdb->get_results( $wpdb->prepare(
        "SELECT e.*,s.funnelstep_type FROM $table e 
                        LEFT JOIN $steps s ON e.step_id = s.ID 
                        WHERE e.status = %s AND s.funnelstep_type = %s AND %d <= time AND time <= %d"
        , 'complete', 'send_email', $thirty_days_ago, current_time( 'timestamp' ) )
    ));
    ?>
    <tr>
        <td>
            <span class="alignleft"><?php _e( 'Emails Sent' ); ?></span>
        </td>
        <th>
            <span class="alignright"><?php echo $emails_sent; ?></span>
        </th>
    </tr>
    <?php

    /* Email Opens */
    $table = $wpdb->prefix . WPGH_ACTIVITY;
    $emails_opened = $wpdb->get_var( $wpdb->prepare(
        "SELECT count(*) FROM $table WHERE activity_type = %s AND timestamp >= %d AND funnel_id != 0 AND step_id != 0"
        , 'email_opened', $thirty_days_ago )
    );

    ?>
    <tr>
        <td>
            <span class="alignleft"><?php _e( 'Email Opens' ); ?></span>
        </td>
        <th>
            <span class="alignright"><?php echo $emails_opened; ?></span>
        </th>
    </tr>
    <?php

    /* Email Clicks */
    $table = $wpdb->prefix . WPGH_ACTIVITY;
    $link_clicks = $wpdb->get_var( $wpdb->prepare(
        "SELECT count(*) FROM $table WHERE activity_type = %s AND timestamp >= %d AND funnel_id != 0 AND step_id != 0"
        , 'email_link_click', $thirty_days_ago )
    );
    ?>

    <tr>
        <td>
            <span class="alignleft"><?php _e( 'Email Link Clicks' ); ?></span>
        </td>
        <th>
            <span class="alignright"><?php echo $link_clicks; ?></span>
        </th>
    </tr>
    <?php

    /* Unsubscribes */

    ?></table></div><?php
}