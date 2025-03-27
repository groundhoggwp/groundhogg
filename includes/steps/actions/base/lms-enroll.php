<?php

namespace Groundhogg\Steps\Actions\Base;

use Groundhogg\Step;
use Groundhogg\Steps\Actions\Action;
use function Groundhogg\bold_it;
use function Groundhogg\create_user_from_contact;
use function Groundhogg\html;
use function Groundhogg\one_of;

abstract class LMS_Enroll extends Action {

	/**
	 * Get the element name
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'Change Enrollment', 'groundhogg' );
	}

	/**
	 * Get the element type
	 *
	 * @return string
	 */
	public function get_type() {
		return 'lms_enroll';
	}

	public function get_sub_group() {
		return 'lms';
	}

	/**
	 * The icon
	 *
	 * @return string
	 */
	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/lms/lms-enroll.svg';
	}

	/**
	 * Get the description
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Enroll students in a course or unenroll students form a course.', 'groundhogg' );
	}

	/**
	 * Display the settings based on the given ID
	 *
	 * @param $step Step
	 */
	public function settings( $step ) {

		echo html()->e('p', [], 'Select an action to take...' );

		echo html()->dropdown( [
			'name'        => $this->setting_name_prefix( 'action' ),
			'id'          => $this->setting_id_prefix( 'action' ),
			'options'     => [
				'enroll'   => __( 'Enroll Student', 'groundhogg' ),
				'unenroll' => __( 'Un-enroll Student', 'groundhogg' ),
			],
			'selected'    => $this->get_setting( 'action' ),
			'multiple'    => false,
			'option_none' => false,
		] );

		$course_id = absint( $this->get_setting( 'course' ) );

		echo html()->e('p', [], 'In which course?' );

		echo html()->select2( [
			'id'       => $this->setting_id_prefix( 'course' ),
			'name'     => $this->setting_name_prefix( 'course' ),
			'data'     => $this->get_courses_for_select(),
			'selected' => [ $course_id ],
		] );

		?><p></p><?php
	}

	public function generate_step_title( $step ) {

		$action = $this->get_setting( 'action', 'enroll' );
		$course_id = absint( $this->get_setting( 'course' ) );

		if ( ! $course_id ){
			return 'Select a course';
		}

		if ( $action === 'enroll' ) {
			return sprintf( '<b>Enroll</b> in %s', bold_it( get_the_title( $course_id ) ) );
		}

		return sprintf( '<b>Un-enroll</b> from %s', bold_it( get_the_title( $course_id ) ) );
	}

	public function get_settings_schema() {
		return [
			'course' => [
				'default'  => 0,
				'sanitize' => 'absint'
			],
			'action' => [
				'default'  => 0,
				'sanitize' => function ( $value ) {
					return one_of( $value, [ 'enroll', 'unenroll' ] );
				}
			],
		];
	}

	/**
	 * Get courses for a select2 picker
	 *
	 * @return array
	 */
	abstract protected function get_courses_for_select();

	/**
	 * Enroll in the course
	 *
	 * @param $user_id int
	 *
	 * @return mixed
	 */
	abstract protected function enroll_in_course( $user_id );

	/**
	 * Remove from a course
	 *
	 * @param $user_id int
	 *
	 * @return mixed
	 */
	abstract protected function unenroll_in_course( $user_id );

	/**
	 * @param \Groundhogg\Contact $contact
	 * @param \Groundhogg\Event   $event
	 *
	 * @return bool|void
	 */
	public function run( $contact, $event ) {
		$user_id = $contact->get_user_id();

		$action = $this->get_setting( 'action', 'enroll' );

		if ( ! $user_id ) {
			$user_id = create_user_from_contact( $contact, $this->get_student_role(), 'user' );
		}

		switch ( $action ) {
			case 'enroll':
				return $this->enroll_in_course( $user_id );
				break;
			case 'unenroll':
				return $this->unenroll_in_course( $user_id );
				break;
		}

		return false;
	}

	/**
	 * The role that should be applied to new users created to view courses
	 *
	 * @return string
	 */
	protected function get_student_role() {
		return 'subscriber';
	}
}
