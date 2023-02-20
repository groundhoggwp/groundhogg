<?php

namespace Groundhogg\Steps\Benchmarks\Base;

use Groundhogg\Api\V4\Properties_Api;
use Groundhogg\Contact;
use Groundhogg\Step;
use Groundhogg\Steps\Benchmarks\Benchmark;
use function Groundhogg\array_bold;
use function Groundhogg\bold_it;
use function Groundhogg\get_contactdata;
use function Groundhogg\html;
use function Groundhogg\orList;

abstract class LMS_Integration extends Benchmark {

	public function get_sub_group() {
		return 'lms';
	}

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

		if ( ! in_array( $this->get_data( 'action' ), $actions ) ) {
			return false;
		}

		if ( ! empty( $course_ids ) && ! in_array( $this->get_data( 'course' ), $course_ids ) ) {
			return false;
		}

		// Don't check lessons when not relevant
		if ( $this->get_data( 'action' ) === 'lesson_completed' ) {

			if ( ! empty( $course_ids ) && ! empty( $lesson_ids ) && ! in_array( $this->get_data( 'lesson' ), $lesson_ids ) ) {
				return false;
			}

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
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/lms-enrolled.svg';
	}

	/**
	 * Display the settings based on the given ID
	 *
	 * @param $step Step
	 */
	public function settings( $step ) {
		$actions    = wp_parse_list( $this->get_setting( 'action' ) );
		$course_ids = wp_parse_id_list( $this->get_setting( 'course' ) );
		$lesson_ids = wp_parse_id_list( $this->get_setting( 'lesson' ) );

		echo html()->e( 'p', [], __( 'When a contact...', 'groundhogg' ) );

		echo html()->select2( [
			'id'       => $this->setting_id_prefix( 'action' ),
			'name'     => $this->setting_name_prefix( 'action' ) . '[]',
			'class'    => 'gh-select2',
			'options'  => [
				'course_enrolled'  => __( 'Enrolls in a course', 'groundhogg' ),
				'course_completed' => __( 'Completes a course', 'groundhogg' ),
				'lesson_completed' => __( 'Completes a lesson', 'groundhogg' ),
			],
			'selected' => $actions,
			'multiple' => true,
		] );

		echo html()->e( 'p', [], __( 'For any of the following courses...', 'groundhogg' ) );

		echo html()->select2( [
			'id'          => $this->setting_id_prefix( 'course' ),
			'name'        => $this->setting_name_prefix( 'course' ) . '[]',
			'data'        => $this->get_courses_for_select(),
			'selected'    => $course_ids,
			'class'       => 'gh-select2',
			'multiple'    => true,
			'placeholder' => 'Any course'
		] );

		echo html()->e( 'p', [], __( 'And for any of the following lessons... <i>Only relevant for lesson events.</i>', 'groundhogg' ) );

		echo html()->select2( [
			'id'          => $this->setting_id_prefix( 'lesson' ),
			'name'        => $this->setting_name_prefix( 'lesson' ) . '[]',
			'data'        => $this->get_lessons_for_select( $course_ids ),
			'selected'    => $lesson_ids,
			'multiple'    => true,
			'placeholder' => 'Any lesson'
		] );


		?><p></p><?php

	}

	public function generate_step_title( $step ) {

		$actions    = wp_parse_list( $this->get_setting( 'action' ) );

        if ( empty( $actions ) ){
            return 'LMS event';
        }

		$course_ids = wp_parse_id_list( $this->get_setting( 'course' ) );
		$lesson_ids = wp_parse_id_list( $this->get_setting( 'lesson' ) );

		$courses = array_map( 'get_the_title', $course_ids );
		$lessons = array_map( 'get_the_title', $lesson_ids );

		$courses = orList( array_bold( $courses ) );

		if ( empty( $courses ) ) {
			$courses = bold_it( 'any course' );
		}

		$lessons = orList( array_bold( $lessons ) );

		if ( empty( $lessons ) ) {
			$lessons = bold_it( 'any lesson' );
		}

		if ( count( $actions ) === 1 ) {
			switch ( $actions[0] ) {
				default:
				case 'course_enrolled':
					return sprintf( 'Enrolls in %s', $courses );
				case 'course_completed':
					return sprintf( 'Completes %s', $courses );
				case 'lesson_completed':
					return sprintf( 'Completes %s in %s', $lessons, $courses );
			}
		}

        if ( in_array( 'lesson_completed', $actions ) ){

            $events = [
	            'course_enrolled'  => __( 'Enrolls in a course', 'groundhogg' ),
	            'course_completed' => __( 'Completes a course', 'groundhogg' ),
	            'lesson_completed' => __( 'Completes a lesson', 'groundhogg' )
            ];

            $actions = array_intersect_key( $events, array_combine( $actions, $actions ) );

	        return orList( array_bold( array_values( $actions ) ) );
        }

        $actions = orList( array_bold( [ 'Completes', 'Enrolls in' ] ) );

        return sprintf( '%s %s', $actions, $courses );
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
