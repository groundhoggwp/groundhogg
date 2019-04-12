<?php
/**
 * Upgrade
 *
 * @package     Includes
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 1.0.16
 */

class WPGH_Upgrade{

    /**
     * @var string the version which is registered in the DB
     */
    public $db_version;

    /**
     * @var string the version which is set by the plugin
     */
    public $current_version;

    /**
     * WPGH_Upgrade constructor.
     */
    public function __construct()
    {

        $this->db_version = get_option( 'wpgh_last_upgrade_version' );

        if ( ! $this->db_version ){
            $this->db_version = '1.0';
            update_option( 'wpgh_last_upgrade_version', $this->db_version );
        }

        $this->current_version = WPGH()->version;
        add_action( 'admin_init', array( $this, 'do_upgrades' ) );

    }

    /**
     * Check whether upgrades should happen or not.
     */
    public function do_upgrades()
    {
        /**
         * Check if the current version is larger than the version last checked by the upgrader
         */
        if ( version_compare( $this->current_version, $this->db_version, '>' ) ){
            $this->upgrade_path();
            update_option( 'wpgh_last_upgrade_version', $this->current_version );
        }

    }

    /**
     * This function is nice and all you have to do is just enter the version you want to update to.
     */
    private function upgrade_path()
    {
        $this->update_to_version( '1.0.16' );
        $this->update_to_version( '1.0.18.1' );
        $this->update_to_version( '1.0.20' );
        $this->update_to_version( '1.2' );
        $this->update_to_version( '1.2.4' );
        $this->update_to_version( '1.2.6' );
        $this->update_to_version( '1.2.10.3' );
        $this->update_to_version( '1.3' );
        $this->update_to_version( '1.3.5' );
    }

    /**
     * Takes the current version number and converts it to a function which can be clled to perform the upgrade requirements.
     *
     * @param $version string
     * @return bool|string
     */
    private function convert_version_to_function( $version )
    {

        $nums = explode( '.', $version );
        $func = sprintf( 'version_%s', implode( '_', $nums ) );

        if ( method_exists( $this, $func ) ){
            return $func;
        }

        return false;

    }

    private function update_to_version( $version )
    {
        /**
         * Check if the version we want to update to is greater than that of the db_version
         */
        if ( version_compare( $this->db_version, $version, '<' ) ){

            $func = $this->convert_version_to_function( $version );

            if ( $func && method_exists( $this, $func ) ){

                call_user_func( array( $this, $func ) );

                $this->db_version = $version;

                update_option( 'wpgh_last_upgrade_version', $this->db_version );
            }

        }

    }

    /**
     * Perform the upgrades for version 1.0.16
     * apply tags for all the roles a user had to the associated contact
     */
    public function version_1_0_16()
    {
        /* convert users to contacts */
        $args = array(
            'fields' => 'all_with_meta'
        );

        $users = get_users( $args );

        /* @var $wp_user WP_User */
        foreach ( $users as $wp_user ) {

            $contact = wpgh_get_contact( $wp_user->ID, true );

            if ( $contact && $contact->exists() ){
                $contact->add_tag( wpgh_get_roles_pretty_names( $wp_user->roles ) );
            }

        }

    }

    /**
     * Add New Reports Roles to Administrator and Marketer
     */
    public function version_1_0_18_1()
    {
        global $wp_roles;

        //add new roles for reports
        $wp_roles->add_cap( 'administrator', 'view_reports' );
        $wp_roles->add_cap( 'administrator', 'export_reports' );
        $wp_roles->add_cap( 'marketer', 'view_reports' );
        $wp_roles->add_cap( 'marketer', 'export_reports' );

        //clear the cron event from the old 10 minute schedule and put it on the 5 minute schedule.
        wp_clear_scheduled_hook( 'wpgh_cron_event' );

    }

    /**
     * Remove old cron job and make way for better naming.
     */
    public function version_1_0_19_5()
    {
        wp_clear_scheduled_hook( 'wpgh_cron_event' );
    }

    /**
     * Upgrade Activity DB to include Email ID for tracking purposes.
     */
    public function version_1_0_20()
    {
        WPGH()->activity->create_table();
    }

	/**
	 * Create the new SMS table
     * Update the existing Events table for new event type
     * Update All Events to have funnel type
	 * Add appropriate caps to the users
	 * migrate live SMS steps to the new DB
	 */
    public function version_1_2()
    {
        global $wpdb;

        /* ADD SMS TABLE */
    	WPGH()->sms->create_table();
    	/* ADD SMS ROLES*/
        WPGH()->roles->add_caps();
        /* UPDATE EVENTS TABLE */
        WPGH()->events->create_table();
        $events_table = WPGH()->events->table_name;
        /* UPDATE EXISTING EVENT TYPES */
        $wpdb->query( $wpdb->prepare("UPDATE $events_table SET event_type = %d WHERE funnel_id = %d",GROUNDHOGG_BROADCAST_EVENT, WPGH_BROADCAST ) );
        $wpdb->query( $wpdb->prepare("UPDATE $events_table SET event_type = %d WHERE funnel_id > %d",GROUNDHOGG_FUNNEL_EVENT, WPGH_BROADCAST ) );

        /* MIGRATE EXISTING SMS TO DB TABLE */
        $sms_steps = WPGH()->steps->get_steps( array(
    		'step_type' => 'send_sms'
	    ) );

    	if ( ! empty( $sms_steps ) ){

    		foreach ($sms_steps as $step ){
    			$step = wpgh_get_funnel_step( $step->ID );
    			$message = $step->get_meta( 'text_message' );

    			if ( $message ){

                    $title = wp_trim_words( $message, 10 );

                    $sms_id = WPGH()->sms->add( array(
                        'title' => $title,
                        'message' => $message
                    ) );

                    if ( $sms_id ){
                        $step->update_meta( 'sms_id', $sms_id );
                    }

    			}

            }

	    }
    }

    /**
     * Allow for emails to be saved as templates in the templates page.
     */
    public function version_1_2_4()
    {
        WPGH()->emails->create_table();
    }

	/**
	 * Make the broadcasts table SMS compatible.
	 */
    public function version_1_2_6()
    {
	    global $wpdb;
	    $table = WPGH()->broadcasts->table_name;
	    $wpdb->query( "ALTER TABLE $table CHANGE `email_id` `object_id` bigint(20) unsigned;" );
	    WPGH()->broadcasts->create_table();
	    $wpdb->query( $wpdb->prepare("UPDATE $table SET object_type = %s WHERE object_type = '';", 'email' ) );

    }

    /**
     * Add send SMS caps to admin and marketer.
     */
    public function version_1_2_10_3()
    {
        global $wp_roles;

        //add new roles for reports
        $wp_roles->add_cap( 'administrator', 'send_sms' );
        $wp_roles->add_cap( 'marketer', 'send_sms' );
    }

    /**
     * Add default tag associations.
     */
    public function version_1_3()
    {
        wpgh_update_option( 'gh_optin_status_job', true );
        WPGH()->status_tag_mapper->install_default_tags();
    }

	/**
	 * install other caps.
	 */
    public function version_1_3_5()
    {
		WPGH()->roles->add_caps();
    }
}