<?php

namespace Groundhogg\Steps\Actions\Base;

use Groundhogg\Step;
use Groundhogg\Steps\Actions\Action;
use function Groundhogg\create_user_from_contact;
use function Groundhogg\html;

abstract class LMS_Enroll extends Action
{

    /**
     * Get the element name
     *
     * @return string
     */
    public function get_name()
    {
        return __( 'Change Enrollment', 'groundhogg' );
    }

    /**
     * Get the element type
     *
     * @return string
     */
    public function get_type()
    {
        return 'lms_enroll';
    }

	/**
	 * The icon
	 *
	 * @return string
	 */
    public function get_icon(){
        return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/lms.png';
    }

    /**
     * Get the description
     *
     * @return string
     */
    public function get_description()
    {
        return __( 'Enroll students in a course or unenroll students form a course.', 'groundhogg' );
    }

    /**
     * Display the settings based on the given ID
     *
     * @param $step Step
     */
    public function settings($step)
    {
        html()->start_form_table();

	    // ACTION
	    html()->start_row();

	    html()->th([
		    __('Action', 'groundhogg')
	    ]);

	    html()->td([
		    html()->dropdown([
			    'name' => $this->setting_name_prefix('action'),
			    'id' => $this->setting_id_prefix('action'),
			    'class' => 'auto-save',
			    'options' => [
				    'enroll' => __('Enroll Student', 'groundhogg'),
				    'unenroll' => __('Unenroll Student', 'groundhogg'),
			    ],
			    'selected' => $this->get_setting('action'),
			    'multiple' => false,
			    'option_none' => false,
		    ])
	    ]);

	    html()->end_row();

        // COURSE
        html()->start_row();

        html()->th([
            __('Course', 'groundhogg')
        ]);

        $course_id = absint($this->get_setting('course'));

        html()->td([
            html()->select2([
                'id' => $this->setting_id_prefix('course'),
                'name' => $this->setting_name_prefix('course'),
                'data' => $this->get_courses_for_select(),
                'selected' => [$course_id],
                'class' => 'gh-select2'
            ])
        ]);

        html()->end_row();

        html()->end_form_table();
    }

    /**
     * Save the step based on the given ID
     *
     * @param $step Step
     */
    public function save($step)
    {
        $this->save_setting( 'course', absint( $this->get_posted_data( 'course' ) ) );
        $this->save_setting( 'action', sanitize_key( $this->get_posted_data( 'action' ) ) );
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
     * @param \Groundhogg\Event $event
     * @return bool|void
     */
    public function run($contact, $event)
    {
        $user_id = $contact->get_user_id();

        $action = $this->get_setting( 'action', 'enroll' );

        if ( ! $user_id ){
            $user_id = create_user_from_contact( $contact, $this->get_student_role(), 'user' );
        }

        switch ( $action ){
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
    protected function get_student_role()
    {
        return 'subscriber';
    }
}
