<?php

namespace Groundhogg\Steps\Actions;

use Groundhogg\Classes\Task;
use Groundhogg\Contact;
use Groundhogg\Event;
use Groundhogg\Step;
use Groundhogg\Utils\DateTimeHelper;
use function Groundhogg\bold_it;
use function Groundhogg\do_replacements;
use function Groundhogg\html;
use function Groundhogg\Ymd_His;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Apply Note
 *
 * Apply a note to a contact through the funnel builder.
 *
 * @since       File available since Release 0.9
 * @subpackage  Elements/Actions
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Elements
 */
class Create_Task extends Action {

	/**
	 * @return string
	 */
	public function get_help_article() {
		return 'https://docs.groundhogg.io/docs/builder/actions/apply-note/';
	}

	/**
	 * ] et the element name
	 *
	 * @return string
	 */
	public function get_name() {
		return _x( 'Create Task', 'step_name', 'groundhogg' );
	}

	/**
	 * Get the element type
	 *
	 * @return string
	 */
	public function get_type() {
		return 'create_task';
	}

	public function get_sub_group() {
		return 'crm';
	}

	/**
	 * Get the description
	 *
	 * @return string
	 */
	public function get_description() {
		return _x( 'Create a new task', 'step_description', 'groundhogg' );
	}

	/**
	 * Get the icon URL
	 *
	 * @return string
	 */
	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/create-task.svg';
	}

	/**
	 * @param $step Step
	 */
	public function settings( $step ) {

		echo html()->e( 'p', [], __( 'Briefly summarize the task in a few words...', 'groundhogg' ) );

		echo html()->input( [
			'id'          => $this->setting_id_prefix( 'summary' ),
			'name'        => $this->setting_name_prefix( 'summary' ),
			'value'       => $this->get_setting( 'summary', '' ),
			'placeholder' => 'Call the contact...'
		] );

		echo html()->e( 'p', [], __( 'Provide some additional details to help the task be completed...', 'groundhogg' ) );

		echo html()->textarea( [
			'id'    => $this->setting_id_prefix( 'task_content' ),
			'name'  => $this->setting_name_prefix( 'content' ),
			'value' => $this->get_setting( 'content' )
		] );

		echo html()->e( 'p', [], __( 'When should the task be completed by?', 'groundhogg' ) );

		echo html()->e( 'div', [
			'class' => 'gh-input-group'
		], [
			html()->input( [
				'type'  => 'number',
				'class' => 'input',
				'value' => $this->get_setting( 'delay_amount', 7 ),
				'name'  => $this->setting_name_prefix( 'delay_amount' )
			] ),
			html()->dropdown( [
				'options'     => [
					'days'   => __( 'Days' ),
					'weeks'  => __( 'Weeks' ),
					'months' => __( 'Months' ),
				],
				'selected'    => $this->get_setting( 'delay_unit', 'days' ),
				'name'        => $this->setting_name_prefix( 'delay_unit' ),
				'option_none' => false,
			] ),
			html()->input( [
				'type'  => 'time',
				'class' => 'input',
				'value' => $this->get_setting( 'time', '17:00:00' ),
				'name'  => $this->setting_name_prefix( 'time' )
			] ),
		] );

		echo html()->e( 'p', [], __( 'What type of task is it?', 'groundhogg' ) );

		echo html()->dropdown( [
			'selected'    => $this->get_setting( 'task_type', 'task' ),
			'name'        => $this->setting_name_prefix( 'task_type' ),
			'option_none' => false,
			'options'     => [
				'task'    => __( 'General Task', 'groundhogg' ),
				'call'    => __( 'Call', 'groundhogg' ),
				'email'   => __( 'Email', 'groundhogg' ),
				'meeting' => __( 'Meeting', 'groundhogg' ),
			]
		] );

		?><p></p><?php
	}

	/**
	 * Save the step settings
	 *
	 * @param $step Step
	 */
	public function save( $step ) {

//        $this->save_setting( 'content', wp_kses_post( $this->get_posted_data( 'content' ) ) );
		$this->save_setting( 'summary', sanitize_text_field( $this->get_posted_data( 'summary' ) ) );
		$this->save_setting( 'task_type', sanitize_text_field( $this->get_posted_data( 'task_type' ) ) );
		$this->save_setting( 'time', sanitize_text_field( $this->get_posted_data( 'time', '17:00:00' ) ) );
		$this->save_setting( 'delay_unit', sanitize_text_field( $this->get_posted_data( 'delay_unit', '17:00:00' ) ) );
		$this->save_setting( 'delay_amount', absint( $this->get_posted_data( 'delay_amount', 7 ) ) );

	}

	public function generate_step_title( $step ) {

		$summary = $this->get_setting( 'summary' );

		if ( empty( $summary ) ) {
			return 'Create a new task';
		}

		return sprintf( 'Create task %s', bold_it( $summary ) );
	}

	/**
	 * Process the apply note step...
	 *
	 * @param $contact Contact
	 * @param $event   Event
	 *
	 * @return true;
	 */
	public function run( $contact, $event ) {

		$summary = $this->get_setting( 'summary' );
		$content = $this->get_setting( 'content' );
		$content = do_replacements( $content, $contact );

		$amount = $this->get_setting( 'delay_amount', 7 );
		$unit   = $this->get_setting( 'delay_unit', 'days' );
		$time   = $this->get_setting( 'time', '17:00:00' );
		$type   = $this->get_setting( 'task_type', 'task' );

		$dueDate = new DateTimeHelper();

		$dueDate->modify( "+$amount $unit" );
		$dueDate->modify( "$time" );

		$task = new Task( [
			'due_date'    => $dueDate->format( 'Y-m-d H:i:s' ),
			'summary'     => $summary,
			'content'     => wp_kses_post( $content ),
			'step_id'     => $event->get_step_id(),
			'funnel_id'   => $event->get_funnel_id(),
			'object_id'   => $contact->get_id(),
			'object_type' => 'contact',
			'context'     => 'funnel',
			'user_id'     => $contact->get_owner_id(),
			'type'        => $type,
		] );

		return true;
	}
}
