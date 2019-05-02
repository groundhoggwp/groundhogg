<?php
namespace Groundhogg\Steps\Actions;

use Groundhogg\HTML;
use Groundhogg\Plugin;
use Groundhogg\Step;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Send Email
 *
 * This will send an email to the contact using WP_MAIL
 *
 * @package     Elements
 * @subpackage  Elements/Actions
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @see         WPGH_Email::send()
 * @since       File available since Release 0.9
 */

class Send_Email extends Action
{

    /**
     * Get the element name
     *
     * @return string
     */
    public function get_name()
    {
        return  _x( 'Send Email', 'action_name', 'groundhogg' );
    }

    /**
     * Get the element type
     *
     * @return string
     */
    public function get_type()
    {
        return 'send_email';
    }

    /**
     * Get the description
     *
     * @return string
     */
    public function get_description()
    {
        return _x( 'Send an email to a contact.', 'element_description', 'groundhogg' );
    }

    /**
     * Get the icon URL
     *
     * @return string
     */
    public function get_icon()
    {
        return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/send-email.png';
    }

    public function scripts(){
        wp_enqueue_script('groundhogg-funnel-email' );

        wp_localize_script('groundhogg-funnel-email', 'groundhoggEmailStep', array(
            'edit_email_path' => admin_url( 'admin.php?page=gh_emails&action=edit' ),
            'add_email_path' => admin_url( 'admin.php?page=gh_emails&action=add' ),
            'save_changes_prompt' => _x( "You have changes which have not been saved. Are you sure you want to exit?", 'notice', 'groundhogg' ),
        ));
    }

    /**
     * Display the settings
     *
     * @param $step Step
     */
    public function settings( $step )
    {

        $html = Plugin::$instance->utils->html;

        $email_id = $this->get_setting( 'email_id' );
        $email = Plugin::$instance->utils->get_email( $email_id );

        $html->start_form_table();

        $html->start_row();

        $html->th( __( 'Select an email to send:', 'groundhogg' ) );
        $html->td( [
            $html->dropdown_emails( [
                'name'  => $this->setting_name_prefix( 'email_id' ),
                'id'    => $this->setting_id_prefix(   'email_id' ),
                'selected' => $this->get_setting( 'email_id' ),
            ] ),
            "<div class=\"row-actions\">",
            $html->button( [
                'title'     => 'Edit Email',
                'text'      => _x( 'Edit Email', 'action', 'groundhogg' ),
                'class'     => 'button button-primary edit-email',
            ] ),
            $html->button( [
                'title'     => 'Create New Email',
                'text'      => _x( 'Create New Email', 'action', 'groundhogg' ),
                'class'     => 'button button-secondary add-email',
            ] ),
            "</div>"
        ] );

        $html->end_row();

        if ( $email && $email->is_confirmation_email() ){

            $html->add_form_control( [
                'label' => __( 'Skip if confirmed?', 'groundhogg' ),
                'type' => HTML::CHECKBOX,
                'field' => [
                    'name'  => $this->setting_name_prefix( 'skip_if_confirmed' ),
                    'id'    => $this->setting_id_prefix(   'skip_if_confirmed' ),
                    'label' => __( 'Enable', 'groundhogg' ),
                    'checked' => (bool) $this->get_setting( 'skip_if_confirmed' )
                ],
                'description' =>  __( 'Skip to next <b>Email Confirmed</b> benchmark if email is already confirmed.', 'groundhogg' ),
            ] );

        }

        $html->end_form_table();

        echo $html->input( [
            'type'  => 'hidden',
            'name'  => $this->setting_name_prefix( 'add_email_override' ),
            'id'    => $this->setting_id_prefix(   'add_email_override' ),
            'class' => 'add-email-override',
        ] );
    }

    /**
     * Extend the EMAIL reporting VIEW with open rates...
     *
     * @param $step WPGH_Step
     */
    public function reporting($step)
    {
        parent::reporting($step);

        $start_time = WPGH()->menu->funnels_page->reporting_start_time;
        $end_time   = WPGH()->menu->funnels_page->reporting_end_time;

        $cquery = new WPGH_Contact_Query();

        $num_opens = $cquery->query( array(
            'count' => true,
            'activity' => array(
                'start' => $start_time,
                'end'   => $end_time,
                'step'  => $step->ID,
                'funnel'=> $step->funnel_id,
                'activity_type'  => 'email_opened'
            )
        ) );

        $num_clicks = $cquery->query( array(
            'count' => true,
            'activity' => array(
                'start' => $start_time,
                'end'   => $end_time,
                'step'  => $step->ID,
                'funnel'=> $step->funnel_id,
                'activity_type'  => 'email_link_click'
            )
        ) );

        ?>
        <hr>
        <p class="report">
        <span class="opens"><?php _ex( 'Opens', 'stats', 'groundhogg' ); ?>:&nbsp;
            <strong>
                <a href="<?php echo admin_url( sprintf( 'admin.php?page=gh_contacts&view=activity&funnel=%s&step=%s&activity_type=%s&start=%s&end=%s',
                        $step->funnel_id,
                        $step->ID,
                        'email_opened',
                        $start_time,
                        $end_time )
                );?>" target="_blank"><?php echo $num_opens; ?></a>
            </strong>
        </span> |
            <span class="clicks"><?php _ex( 'Clicks', 'stats', 'groundhogg' ); ?>:&nbsp;
                <strong>
                <a href="<?php echo admin_url( sprintf( 'admin.php?page=gh_contacts&view=activity&funnel=%s&step=%s&activity_type=%s&start=%s&end=%s',
                        $step->funnel_id,
                        $step->ID,
                        'email_link_click',
                        $start_time,
                        $end_time )
                );?>" target="_blank"><?php echo $num_clicks; ?></a>
            </strong>
        </span> |
            <span class="ctr"><?php _ex( 'C.T.R', 'stats', 'groundhogg' ); ?>:&nbsp;<strong><?php echo round( ( $num_clicks / ( ( $num_opens > 0 )? $num_opens : 1 ) * 100 ), 2 ); ?></strong>%</span>
        </p>
        <?php

        do_action( 'wpgh_email_reporting_after', $step );
    }

    /**
     * Save the settings
     *
     * @param $step WPGH_Step
     */
    public function save( $step )
    {

        if ( isset( $_POST[ $step->prefix( 'email_id' ) ] ) ){

            $email_id = intval(  $_POST[ $step->prefix( 'email_id' ) ] );

            /**
             * Hack for adding new emails and saving.
             */
            if ( isset( $_POST[ $step->prefix( 'add_email_override' ) ] ) && ! empty( $_POST[ $step->prefix( 'add_email_override' ) ] ) ){
                $email_id = intval(  $_POST[ $step->prefix( 'add_email_override' ) ] );
            }

            $step->update_meta( 'email_id', $email_id );

            $email = WPGH()->emails->get( $email_id );

            if ( $email->status === 'draft' && $step->is_active() ){

                WPGH()->menu->funnels_page->notices->add( 'contains-drafts', _x( 'Your funnel contains email steps which are in draft mode. Please ensure all your emails are marked as ready.', 'notice', 'groundhogg' ), 'info' );

            }

        }

        if ( isset( $_POST[ $step->prefix( 'skip_if_confirmed' ) ] ) ){

            $step->update_meta( 'skip_if_confirmed', 1 );

        } else {

            $step->delete_meta( 'skip_if_confirmed' );

        }

    }

    /**
     * Process the apply note step...
     *
     * @param $contact WPGH_Contact
     * @param $event WPGH_Event
     *
     * @return bool|WP_Error
     */
    public function run( $contact, $event )
    {

        $email_id = apply_filters( 'wpgh_step_run_send_email_id', $event->step->get_meta( 'email_id' ), $event );

        $email = new WPGH_Email( $email_id );

        if ( $email->is_confirmation_email() ){

            if ( $event->step->get_meta( 'skip_if_confirmed' ) && $contact->optin_status === WPGH_CONFIRMED ){

                /* This will simply get the upcoming email confirmed step and complete it. No muss not fuss */
                do_action( 'wpgh_email_confirmed', $contact, $event->funnel_id );

                /* Return false to avoid enqueue the next step. */
                return false;

            }

        }

        return $email->send( $contact, $event );

    }

    /**
     * Create a new email and set the step email_id to the ID of the new email.
     *
     * @param $step WPGH_Step
     * @param $args array list of args to provide criteria for import.
     */
    public function import( $args, $step )
    {

        if ( ! gisset_not_empty( $args, 'content' ) || ! gisset_not_empty( $args, 'subject' ) ){
            return;
        }

        if ( ! gisset_not_empty( $args, 'content' ) ){
            $args[ 'pre_header' ] = '';
        }

        $email_id = WPGH()->emails->add( array(
            'content'       => $args['content'],
            'subject'       => $args['subject'],
            'pre_header'    => $args['pre_header'],
            'from_user'     => get_current_user_id(),
            'author'        => get_current_user_id()
        ) );

        if ( $email_id ){
            $step->update_meta( 'email_id', $email_id );
        }
    }


    /**
     * Export all tag related steps
     *
     * @param $args array of args
     * @param $step WPGH_Step
     * @return array of tag names
     */
    public function export( $args, $step )
    {
        $email_id = intval( $step->get_meta( 'email_id' ) );

        $email = new WPGH_Email( $email_id );

        if ( ! $email->exists() )
            return $args;

        $args[ 'subject'] = $email->subject;
        $args[ 'pre_header' ] = $email->pre_header;
        $args[ 'content' ] = $email->content;

        return $args;
    }
}