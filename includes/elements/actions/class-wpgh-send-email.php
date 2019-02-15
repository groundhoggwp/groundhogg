<?php
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

if ( ! defined( 'ABSPATH' ) ) exit;

class WPGH_Send_Email extends WPGH_Funnel_Step
{

    /**
     * @var string
     */
    public $type    = 'send_email';

    /**
     * @var string
     */
    public $group   = 'action';

    /**
     * @var string
     */
    public $icon    = 'send-email.png' ;

    /**
     * @var string
     */
    public $name    = 'Send Email';

    /**
     * The time delay before this step can be run.
     *
     * @var int
     */
    public $delay_time = 5;

    /**
     * @var string
     */
    public $description = 'Send an email to a contact.';

    public function __construct()
    {
        $this->name = _x( 'Send Email', 'element_name', 'groundhogg' );
        $this->description = _x( 'Send an email to a contact.', 'element_description', 'groundhogg' );

        parent::__construct();

        if ( is_admin() && isset( $_GET['page'] ) && ( $_GET[ 'page' ] === 'gh_funnels' ||  $_GET[ 'page' ] === 'gh_emails' ) && isset($_REQUEST[ 'action' ]) && $_REQUEST[ 'action' ] === 'edit' ) {
            add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
        }
    }

    public function scripts(){
        wp_enqueue_script(
            'wpgh-email-element',
            WPGH_ASSETS_FOLDER . 'js/admin/funnel-elements/email.min.js',
            array(),
            filemtime( WPGH_PLUGIN_DIR . 'assets/js/admin/funnel-elements/email.min.js' )
        );

        wp_localize_script('wpgh-email-element', 'wpghEmailsBase', array(
            'path' =>  admin_url( 'admin.php?page=gh_emails' ),
            'dontSaveChangesMsg'    => _x( "You have changes which have not been saved. Are you sure you want to exit?", 'notice', 'groundhogg' ),
        ) );
    }

    /**
     * Display the settings
     *
     * @param $step WPGH_Step
     */
    public function settings( $step )
    {
        $email_id = $step->get_meta( 'email_id' );

        if ( ! $email_id ){
            /* If this is for example a NEW step, the lets just set a default email */
            $emails = WPGH()->emails->get_emails();
            $email_id = array_pop( $emails )->ID;
        }

        $email = new WPGH_Email( $email_id );

        $return_path = admin_url( 'admin.php?page=gh_emails&return_funnel=' . $step->funnel_id . '&return_step=' . $step->ID );
        $basic_path = admin_url( 'admin.php?page=gh_emails' );

        ?>
        <table class="form-table">
            <tbody>
            <tr>
                <th>
                    <?php esc_html_e( 'Select an email to send:', 'groundhogg' ); ?>
                </th>
                <td>
                    <?php $args = array(
                        'id'        => $step->prefix( 'email_id' ),
                        'name'      => $step->prefix( 'email_id' ),
                        'selected'  => array( $email_id ),
                    );

                    echo WPGH()->html->dropdown_emails( $args ); ?>
                    <div class="row-actions">

                        <?php echo WPGH()->html->modal_link( array(
                            'title'     => 'Edit Email',
                            'text'      => _x( 'Edit Email', 'action', 'groundhogg' ),
                            'footer_button_text' => __( 'Close' ),
                            'id'        => '',
                            'class'     => 'button button-primary edit-email',
                            'source'    => $basic_path . '&email=' . $email_id . '&action=edit',
                            'height'    => 900,
                            'width'     => 1500,
                            'footer'    => 'true',
                            'preventSave'    => 'false',
                        )); ?>
                        <?php echo WPGH()->html->modal_link( array(
                            'title'     => 'Create New Email',
                            'text'      => _x( 'Create New Email', 'action', 'groundhogg' ),
                            'footer_button_text' => __( 'Close' ),
                            'id'        => '',
                            'class'     => 'button button-secondary add-email',
                            'source'    => $return_path . '&action=add',
                            'height'    => 900,
                            'width'     => 1500,
                            'footer'    => 'true',
                            'preventSave'    => 'false',
                        )); ?>
                    </div>
                </td>
            </tr>
            <?php if ( $email->is_confirmation_email() ): ?>
            <tr>
                <th><?php _e( 'Skip if confirmed?' ) ?></th>
                <td><?php echo WPGH()->html->checkbox( array(
                        'name'  => $step->prefix( 'skip_if_confirmed' ),
                        'id'    => $step->prefix( 'skip_if_confirmed' ),
                        'value' => 1,
                        'label' => __( 'Skip this email if the contact\'s email is already confirmed.', 'Groundhogg' ),
                        'checked' => $step->get_meta( 'skip_if_confirmed' ),
                    ) ); ?></td>
            </tr>
            <?php endif; ?>
            </tbody>
        </table>

        <?php echo WPGH()->html->input( array(
            'type'  => 'hidden',
            'name'  => $step->prefix( 'add_email_override' ),
            'id'    => $step->prefix( 'add_email_override' ),
            'class' => 'add-email-override',
            'value' => '',
            'attributes' => '',
            'placeholder' => ''
        ) ); ?>

        <?php
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
     * @return bool
     */
    public function run( $contact, $event )
    {

        $email_id = apply_filters( 'wpgh_step_run_send_email_id', $event->step->get_meta( 'email_id' ), $event );

        $email = new WPGH_Email( $email_id );

        if ( $email->is_confirmation_email() ){

            if ( $event->step->get_meta( 'skip_if_confirmed' ) && $contact->optin_status === WPGH_CONFIRMED ){

                /* This will simply get the upcoming email confirmed step and complete it. No muss not fuss */
                do_action( 'wpgh_email_confirmed', $contact, $event->funnel_id );

                return true;

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