<?php

namespace Groundhogg\Steps\Benchmarks\Base;

use Groundhogg\Contact;
use Groundhogg\Step;
use Groundhogg\Steps\Benchmarks\Benchmark;
use function Groundhogg\ensure_array;
use function Groundhogg\get_contactdata;
use function Groundhogg\html;

abstract class LMS_Integration extends Benchmark {

	/**
	 * Do the lifterLMS benchmark
	 *
	 * @param $contact int|string
	 * @param $course  int
	 * @param $lesson  int
	 * @param $action  string
	 * @param $type    string
	 */
	static function do_it( $contact, $course, $lesson, $action, $type = 'lms_action' ) {
		do_action( "groundhogg/lms/{$type}", $contact, $course, $lesson, $action );
	}

	/**
	 * get the hook for which the benchmark will run
	 *
	 * @return int[]
	 */
	protected function get_complete_hooks() {
		return [
			"groundhogg/lms/{$this->get_type()}" => 4
		];
	}

	/**
	 *
	 *
	 * @return string
	 */
	public function get_type() {
		return 'lms_action';
	}

	/**
	 * Get the contact from the data set.
	 *
	 * @return Contact
	 */
	protected function get_the_contact() {
		return get_contactdata( $this->get_data( 'contact' ) );
	}

	/**
	 * @param $contact_id_or_email int|string
	 * @param $course_id           int
	 * @param $lesson_id           int
	 * @param $action              string
	 */
	public function setup( $contact_id_or_email, $course_id, $lesson_id, $action ) {
		$this->add_data( 'contact', $contact_id_or_email );
		$this->add_data( 'course', $course_id );
		$this->add_data( 'lesson', $lesson_id );
		$this->add_data( 'action', $action );
	}

	/**
	 * Based on the current step and contact,
	 *
	 * @return bool
	 */
	protected function can_complete_step() {

		$actions    = wp_parse_list( $this->get_setting( 'action' ) );
		$course_ids = wp_parse_id_list( $this->get_setting( 'course' ) );
		$lesson_ids = wp_parse_id_list( $this->get_setting( 'lesson' ) );

		if ( ! in_array( $this->get_data( 'action' ), $actions ) ){
			return false;
		}

		if ( ! empty( $course_ids ) && ! in_array( $this->get_data( 'course' ), $course_ids ) ){
			return false;
		}

		if ( ! empty( $course_ids ) && ! empty( $lesson_ids ) && ! in_array( $this->get_data( 'lesson' ), $lesson_ids ) ){
			return false;
		}

		return true;

	}

	/**
	 * Get the element name
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'LMS Action', 'groundhogg' );
	}

	/**
	 * Get the description
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Automation for specific LMS based events.', 'groundhogg' );
	}

	/**
	 * Get the icon URL
	 *
	 * @return string
	 */
	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/lms.png';
	}

	/**
	 * Display the settings based on the given ID
	 *
	 * @param $step Step
	 */
	public function settings( $step ) {
		html()->start_form_table();

		// ACTION
		html()->start_row();

		html()->th( [
			__( 'Events', 'groundhogg' )
		] );

		$actions    = wp_parse_list( $this->get_setting( 'action' ) );
		$course_ids = wp_parse_id_list( $this->get_setting( 'course' ) );
		$lesson_ids = wp_parse_id_list( $this->get_setting( 'lesson' ) );

		html()->td( [
			html()->select2( [
				'id'       => $this->setting_id_prefix( 'action' ),
				'name'     => $this->setting_name_prefix( 'action' ) . '[]',
				'class'    => 'gh-select2',
				'options'  => [
					'course_enrolled'  => __( 'Enrolled in a course', 'groundhogg' ),
					'course_completed' => __( 'Completed a course', 'groundhogg' ),
					'lesson_completed' => __( 'Completed a lesson', 'groundhogg' ),
				],
				'selected' => $actions,
				'multiple' => true,
			] )
		] );

		html()->end_row();

		// COURSE
		html()->start_row();

		html()->th( [
			__( 'Filter by course', 'groundhogg' )
		] );

		html()->td( [
			html()->select2( [
				'id'       => $this->setting_id_prefix( 'course' ),
				'name'     => $this->setting_name_prefix( 'course' ) . '[]',
				'data'     => $this->get_courses_for_select(),
				'selected' => $course_ids,
				'class'    => 'gh-select2',
				'multiple' => true,
			] ),
			html()->description( 'Leave empty for any course.' )
		] );

		html()->end_row();

		if ( empty( $course_ids ) ) {
			html()->end_form_table();

			return;
		}


		// COURSE
		html()->start_row();

		html()->th( [
			__( 'Filter by lesson', 'groundhogg' )
		] );

		html()->td( [
			html()->select2( [
				'id'       => $this->setting_id_prefix( 'lesson' ),
				'name'     => $this->setting_name_prefix( 'lesson' ) . '[]',
				'data'     => $this->get_lessons_for_select( $course_ids ),
				'selected' => $this->get_setting( 'lesson' ),
				'multiple' => true,
			] ),
			html()->description( 'Leave empty for any lesson. Update the funnel to see updated choices.' )
		] );

		html()->end_row();

		html()->end_form_table();
	}

	/**
	 * Get the courses for a select2 container
	 *
	 * @return array
	 */
	abstract protected function get_courses_for_select();

	/**
	 * Get the lessons for a select 2 container
	 *
	 * @param $course_ids [] int the ID of the course
	 *
	 * @return mixed
	 */
	abstract protected function get_lessons_for_select( $course_ids );

	/**
	 * Save the step based on the given ID
	 *
	 * @param $step Step
	 */
	public function save( $step ) {
		$this->save_setting( 'action', array_map( 'sanitize_text_field', $this->get_posted_data( 'action', [] ) ) );
		$this->save_setting( 'course', wp_parse_id_list( $this->get_posted_data( 'course' ) ) );
		$this->save_setting( 'lesson', wp_parse_id_list( $this->get_posted_data( 'lesson' ) ) );
	}
}
