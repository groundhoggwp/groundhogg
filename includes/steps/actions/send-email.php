<?php

namespace Groundhogg\Steps\Actions;

use Groundhogg\Classes\Activity;
use Groundhogg\Email;
use Groundhogg\Reporting\New_Reports\Chart_Draw;
use Groundhogg\Reporting\Reporting;
use Groundhogg\Utils\Graph;
use function Groundhogg\get_array_var;
use Groundhogg\Preferences;
use Groundhogg\Contact;
use Groundhogg\Contact_Query;
use Groundhogg\Event;
use function Groundhogg\get_db;
use function Groundhogg\isset_not_empty;
use Groundhogg\HTML;
use function Groundhogg\html;
use Groundhogg\Plugin;
use function Groundhogg\percentage;
use function Groundhogg\search_and_replace_domain;
use Groundhogg\Step;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
class Send_Email extends Action {

	const TYPE = 'send_email';

	/**
	 * @return string
	 */
	public function get_help_article() {
		return 'https://docs.groundhogg.io/docs/builder/actions/send-email/';
	}

	/**
	 * Get the element name
	 *
	 * @return string
	 */
	public function get_name() {
		return _x( 'Send Email', 'step_name', 'groundhogg' );
	}

	/**
	 * Get the element type
	 *
	 * @return string
	 */
	public function get_type() {
		return 'send_email';
	}

	/**
	 * Get the description
	 *
	 * @return string
	 */
	public function get_description() {
		return _x( 'Send an email to a contact.', 'step_description', 'groundhogg' );
	}

	/**
	 * Get the icon URL
	 *
	 * @return string
	 */
	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/send-email.png';
	}

	public function admin_scripts() {
		wp_enqueue_script( 'groundhogg-funnel-email' );
		wp_localize_script( 'groundhogg-funnel-email', 'EmailStep', array(
			'edit_email_path'     => admin_url( 'admin.php?page=gh_emails&action=edit' ),
			'add_email_path'      => admin_url( 'admin.php?page=gh_emails&action=add' ),
			'save_changes_prompt' => _x( "You have changes which have not been saved. Are you sure you want to exit?", 'notice', 'groundhogg' ),
		) );
	}

	/**
	 * Display the settings
	 *
	 * @param $step Step
	 */
	public function settings( $step ) {

		$html = Plugin::$instance->utils->html;

		$email_id = $this->get_setting( 'email_id' );
		$email    = Plugin::$instance->utils->get_email( $email_id );

		$html->start_form_table();

		$html->start_row();

		$html->th( __( 'Select an email to send:', 'groundhogg' ) );
		$html->td( [
			// EMAIL ID DROPDOWN
			$html->dropdown_emails( [
				'name'     => $this->setting_name_prefix( 'email_id' ),
				'id'       => $this->setting_id_prefix( 'email_id' ),
				'selected' => $this->get_setting( 'email_id' ),
			] ),
			// ROW ACTIONS
			"<div style='margin-top: 10px'>",
			// EDIT EMAIL
			$html->button( [
				'title' => 'Edit Email',
				'text'  => _x( 'Edit Email', 'action', 'groundhogg' ),
				'class' => 'button button-primary edit-email',
			] ),
			'&nbsp;',
			// ADD NEW EMAIL
			$html->button( [
				'title' => 'Create New Email',
				'text'  => _x( 'Create New Email', 'action', 'groundhogg' ),
				'class' => 'button button-secondary add-email',
			] ),
			"</div>",
			// ADD EMAIL OVERRIDE
			$html->input( [
				'type'  => 'hidden',
				'name'  => $this->setting_name_prefix( 'add_email_override' ),
				'id'    => $this->setting_id_prefix( 'add_email_override' ),
				'class' => 'add-email-override',
			] )
		] );

		$html->end_row();

		if ( $email && $email->is_confirmation_email() ) {
			$html->add_form_control( [
				'label'       => __( 'Skip if confirmed?', 'groundhogg' ),
				'type'        => HTML::CHECKBOX,
				'field'       => [
					'name'    => $this->setting_name_prefix( 'skip_if_confirmed' ),
					'id'      => $this->setting_id_prefix( 'skip_if_confirmed' ),
					'label'   => __( 'Enable', 'groundhogg' ),
					'checked' => (bool) $this->get_setting( 'skip_if_confirmed' )
				],
				'description' => __( 'Skip to next <b>Email Confirmed</b> benchmark if email is already confirmed.', 'groundhogg' ),
			] );
		}

		$html->end_form_table();
	}

	/**
	 * Extend the EMAIL reporting VIEW with open rates...
	 *
	 * @param $step Step
	 */
	public function reporting( $step ) {
		parent::reporting( $step );

		$times = $this->get_reporting_interval();

		$start_time = $times['start_time'];
		$end_time   = $times['end_time'];

		$cquery = new Contact_Query();

		$num_opens = $cquery->query( array(
			'count'    => true,
			'activity' => array(
				'start'         => $start_time,
				'end'           => $end_time,
				'step'          => $step->get_id(),
				'funnel'        => $step->get_funnel_id(),
				'activity_type' => 'email_opened'
			)
		) );

		$num_clicks = $cquery->query( array(
			'count'    => true,
			'activity' => array(
				'start'         => $start_time,
				'end'           => $end_time,
				'step'          => $step->get_id(),
				'funnel'        => $step->get_funnel_id(),
				'activity_type' => 'email_link_click'
			)
		) );

		?>
        <hr>
        <p class="report">
        <span class="opens"><?php _ex( 'Opens', 'stats', 'groundhogg' ); ?>:&nbsp;
            <strong>
                <a href="<?php echo add_query_arg( [
	                'activity' => [
		                'start'         => $start_time,
		                'end'           => $end_time,
		                'step'          => $step->get_id(),
		                'funnel'        => $step->get_funnel_id(),
		                'activity_type' => 'email_opened'
	                ]
                ], admin_url( 'admin.php?page=gh_contacts' ) ); ?>" target="_blank"><?php echo $num_opens; ?></a>
            </strong>
        </span> |
            <span class="clicks"><?php _ex( 'Clicks', 'stats', 'groundhogg' ); ?>:&nbsp;
                <strong>
                <a href="<?php echo add_query_arg( [
	                'activity' => [
		                'start'         => $start_time,
		                'end'           => $end_time,
		                'step'          => $step->get_id(),
		                'funnel'        => $step->get_funnel_id(),
		                'activity_type' => 'email_link_click'
	                ]
                ], admin_url( 'admin.php?page=gh_contacts' ) ); ?>" target="_blank"><?php echo $num_clicks; ?></a>
            </strong>
        </span> |
            <span class="ctr"><?php _ex( 'C.T.R', 'stats', 'groundhogg' ); ?>:&nbsp;<strong><?php echo round( ( $num_clicks / ( ( $num_opens > 0 ) ? $num_opens : 1 ) * 100 ), 2 ); ?></strong>%</span>
        </p>
		<?php
	}

	/**
	 * @param Step $step Reporting v2
     * @deprecated  version 2.2 use Dashbord APi for adding graphs
	 */
	public function reporting_v2( $step ) {
		parent::reporting_v2( $step );

		?>
        <div class="reporting-results"><?php
		$times = $this->get_reporting_interval();

		$start_time = $times['start_time'];
		$end_time   = $times['end_time'];

		$sent = get_db( 'events' )->query( [
			'step_id' => $step->get_id(),
			'status'  => Event::COMPLETE,
			'before'  => $end_time,
			'after'   => $start_time
		] );

		$opens = get_db( 'activity' )->query( [
			'step_id'       => $step->get_id(),
			'activity_type' => Activity::EMAIL_OPENED,
			'before'        => $end_time,
			'after'         => $start_time,
		] );

		$clicks = get_db( 'activity' )->query( [
			'step_id'       => $step->get_id(),
			'activity_type' => Activity::EMAIL_CLICKED,
			'before'        => $end_time,
			'after'         => $start_time,
		] );

		$total_sent   = count( $sent );
		$total_opens  = count( $opens );
		$total_clicks = count( $clicks );

		$sent   = Reporting::group_by_time( $sent, 'time', 'absint' );
		$opens  = Reporting::group_by_time( $opens, 'timestamp', 'absint' );
		$clicks = Reporting::group_by_time( $clicks, 'timestamp', 'absint' );

		$data = [
			[
				'label' => __( 'Sent', 'groundhogg' ),
				'data'  => $sent
			],
			[
				'label' => __( 'Emails Opened', 'groundhogg' ),
				'data'  => $opens
			],
			[
				'label' => __( 'Emails Clicked', 'groundhogg' ),
				'data'  => $clicks
			]
		];

		$graph = new Graph( $step->get_id(), [
			'mode' => 'time'
		], $data );


		$line_chart = new Chart_Draw( 0, 0 );

//		if ( $graph->has_data() ):

		?>
        <div class="chart">
            <div class="inside" style="height: 250px;">
				<?php $graph->render(); ?>

	            <canvas id="action_email_send" ></canvas>

                <script>
                    //(function ($) {
                    //    var myChart;
					//
                    //    var chart = $('#action_email_send');
					//
                    //    if (myChart != null) {
                    //        myChart.destroy();
                    //    }
					//
                    //    var ctx = chart[0].getContext('2d');
                    //    myChart = new Chart(ctx, {
                    //        "type": "line",
                    //        "data": {
					//
                    //            "datasets": <?php // echo json_encode( [
					//				array_merge( [
					//					'label' => __( 'sent', 'groundhogg' ),
					//					'data'  => $sent,
					//
					//				], $line_chart->get_line_style() ),
					//				array_merge( [
					//					'label' => __( 'Open', 'groundhogg' ),
					//					'data'  => $opens,
					//
					//				], $line_chart->get_line_style() ),
					//				array_merge( [
					//					'label' => __( 'clicked', 'groundhogg' ),
					//					'data'  => $clicks,
					//
					//				], $line_chart->get_line_style() ),
					//			] );  ?>
                    //        },
                    //        "options": <?php //echo json_encode( $line_chart->get_options() ); ?>
                    //    });
                    //})(jQuery);
                </script>
            </div>
        </div>
		<?php

//		endif;

		?>
        <h3><?php _e( 'Activity', 'groundhogg' ); ?></h3>
		<?php

		html()->list_table(
			[
				'class' => 'email_activity'
			],
			[
				__( 'Sent', 'groundhogg' ),
				__( 'Opens (O.R)', 'groundhogg' ),
				__( 'Clicks (C.T.R)', 'groundhogg' ),
			],
			[
				[
					html()->wrap( $total_sent, 'span', [ 'class' => 'number-total' ] ),
					html()->wrap( $total_opens . ' (' . percentage( $total_sent, $total_opens ) . '%)', 'span', [ 'class' => 'number-total' ] ),
					html()->wrap( $total_clicks . ' (' . percentage( $total_opens, $total_clicks ) . '%)', 'span', [ 'class' => 'number-total' ] ),
				]
			],
			false
		);
		?></div><?php

	}

	/**
	 * Save the settings
	 *
	 * @param $step Step
	 */
	public function save( $step ) {
		$email_id = absint( $this->get_posted_data( 'add_email_override', $this->get_posted_data( 'email_id' ) ) );

		$this->save_setting( 'email_id', $email_id );

		$email = new Email( $this->get_setting( 'email_id' ) );

		if ( ! $email->exists() ) {
			$this->add_error( 'email_dne', __( 'You have not selected an email to send in one of your steps.', 'groundhogg' ) );
		}

		if ( ( $email->is_draft() && $step->get_funnel()->is_active() ) ) {
			$this->add_error( 'email_in_draft_mode', __( 'You still have emails in draft mode! These emails will not be sent and will cause automation to stop.' ) );
		}

		$this->save_setting( 'skip_if_confirmed', ( bool ) $this->get_posted_data( 'skip_if_confirmed', false ) );
	}

	/**
	 * Process the apply note step...
	 *
	 * @param $contact Contact
	 * @param $event Event
	 *
	 * @return bool|\WP_Error
	 */
	public function run( $contact, $event ) {

		$email_id = absint( $this->get_setting( 'email_id' ) );
		$email    = Plugin::$instance->utils->get_email( $email_id );

		if ( ! $email ) {
			return new \WP_Error( 'email_dne', 'Invalid email ID provided.' );
		}

		if ( $email->is_confirmation_email() ) {

			if ( $this->get_setting( 'skip_if_confirmed' ) && $contact->get_optin_status() === Preferences::CONFIRMED ) {

				/* This will simply get the upcoming email confirmed step and complete it. No muss not fuss */
				do_action( 'groundhogg/step/email/confirmed', $contact->get_id(), Preferences::CONFIRMED, Preferences::CONFIRMED, $event->get_funnel_id() );

				/* Return false to avoid enqueueing the next step. */

				return false;

			}

		}

		return $email->send( $contact, $event );
	}

	/**
	 * Create a new email and set the step email_id to the ID of the new email.
	 *
	 * @param $step Step
	 * @param $args array list of args to provide criteria for import.
	 */
	public function import( $args, $step ) {

		if ( ! isset_not_empty( $args, 'content' ) || ! isset_not_empty( $args, 'subject' ) ) {
			return;
		}

		if ( ! isset_not_empty( $args, 'content' ) ) {
			$args['pre_header'] = '';
		}

		$email_id = Plugin::$instance->dbs->get_db( 'emails' )->add( [
			'content'    => search_and_replace_domain( $args['content'] ),
			'subject'    => $args['subject'],
			'pre_header' => $args['pre_header'],
			'title'      => get_array_var( $args, 'title', $args['subject'] ),
			'from_user'  => get_current_user_id(),
			'author'     => get_current_user_id()
		] );

		if ( $email_id ) {
			$step->update_meta( 'email_id', $email_id );
		}
	}


	/**
	 * Export all tag related steps
	 *
	 * @param $args array of args
	 * @param $step Step
	 *
	 * @return array of tag names
	 */
	public function export( $args, $step ) {
		$email_id = absint( $step->get_meta( 'email_id' ) );

		$email = Plugin::$instance->utils->get_email( $email_id );

		if ( ! $email || ! $email->exists() ) {
			return $args;
		}

		$args['subject']    = $email->get_subject_line();
		$args['title']      = $email->get_title();
		$args['pre_header'] = $email->get_pre_header();
		$args['content']    = $email->get_content();

		return $args;
	}
}