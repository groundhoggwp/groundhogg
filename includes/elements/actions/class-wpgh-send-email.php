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
            $email_id = array_shift( $emails )->ID;
        }

        $return_path = admin_url( 'admin.php?page=gh_emails&return_funnel=' . $step->funnel_id . '&return_step=' . $step->ID );

        ?>
        <table class="form-table">
            <tbody>
            <tr>
                <th>
                    <?php echo esc_html__( 'Select an email to send:', 'groundhogg' ); ?>
                </th>
                <td>
                    <?php $args = array(
                        'id'        => $step->prefix( 'email_id' ),
                        'name'      => $step->prefix( 'email_id' ),
                        'selected'  => array( $email_id ),
                    );

                    echo WPGH()->html->dropdown_emails( $args ); ?>
                    <div class="row-actions">
                        <a
                            class="editinline"
                            id="<?php echo $step->prefix( 'edit_email' ); ?>"
                            target="_blank"
                            href="<?php echo $return_path . '&email=' . $email_id . '&action=edit' ;?>"
                        ><?php esc_html_e( 'Edit Email', 'groundhogg' );?></a>
                        |
                        <a target="_blank" href="<?php echo $return_path . '&action=add' ;?>" ><?php esc_html_e( 'Create New Email', 'groundhogg' );?></a>
                        <script>
                            jQuery(
                                function($){
                                    $('#<?php echo $step->prefix( 'email_id' ); ?>').change(
                                        function(){
                                            $('#<?php echo $step->prefix( 'edit_email' ); ?>').attr( 'href', '<?php echo $return_path . '&email='; ?>' + $(this).val() )
                                        })
                                });
                        </script>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>

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
        <span class="opens"><?php _e( 'Opens: '); ?>
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
            <span class="clicks"><?php _e( 'Clicks: ' ); ?>
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
            <span class="ctr"><?php _e( 'CTR: '); ?><strong><?php echo round( ( $num_clicks / ( ( $num_opens > 0 )? $num_opens : 1 ) * 100 ), 2 ); ?></strong>%</span>
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

            $step->update_meta( 'email_id', $email_id );

            $email = WPGH()->emails->get( $email_id );

            if ( $email->status === 'draft' && $step->is_active() ){

                WPGH()->menu->funnels_page->notices->add( 'contains-drafts', 'Your funnel contains email steps which are in draft mode. Please ensure all your emails are marked as ready.', 'info' );

            }

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

        $email_id = $event->step->get_meta( 'email_id' );

        $email = new WPGH_Email( $email_id );

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