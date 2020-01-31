<?php

namespace Groundhogg\Steps\Benchmarks\Base;

use Groundhogg\Contact;
use Groundhogg\Step;
use Groundhogg\Steps\Benchmarks\Benchmark;
use function Groundhogg\get_contactdata;
use function Groundhogg\html;

abstract class LMS_Integration extends Benchmark
{

    /**
     * Do the lifterLMS benchmark
     *
     * @param $contact int|string
     * @param $course int
     * @param $lesson int
     * @param $action string
     * @param $type string
     */
    static function do_it( $contact, $course, $lesson, $action, $type='lms_action' ){
        do_action( "groundhogg/lms/{$type}", $contact, $course, $lesson, $action );
    }

    /**
     * get the hook for which the benchmark will run
     *
     * @return int[]
     */
    protected function get_complete_hooks()
    {
        return [
            "groundhogg/lms/{$this->get_type()}" => 4
        ];
    }

    /**
     *
     *
     * @return string
     */
    public function get_type()
    {
        return 'lms_action';
    }

    /**
     * Get the contact from the data set.
     *
     * @return Contact
     */
    protected function get_the_contact()
    {
        return get_contactdata( $this->get_data( 'contact' ) );
    }

    /**
     * @param $contact_id_or_email int|string
     * @param $course_id int
     * @param $lesson_id int
     * @param $action string
     */
    public function setup( $contact_id_or_email, $course_id, $lesson_id, $action ){
        $this->add_data( 'contact', $contact_id_or_email );
        $this->add_data( 'course',  $course_id );
        $this->add_data( 'lesson',  $lesson_id );
        $this->add_data( 'action',  $action );
    }

    /**
     * Based on the current step and contact,
     *
     * @return bool
     */
    protected function can_complete_step()
    {

        $given_action = $this->get_data( 'action' );

        $saved_action = $this->get_setting( 'action' );

        if ( $saved_action !== $given_action ){
            return false;
        }

        $saved_course_id = absint( $this->get_setting( 'course' ) );
        $given_course_id = absint( $this->get_data( 'course' ) );

        switch ( $saved_action ){
            case 'course_enrolled':
            case 'course_completed':
                return $saved_course_id === $given_course_id;
                break;
            case 'lesson_completed':
                $saved_lesson_id = absint( $this->get_setting( 'lesson' ) );
                $given_lesson_id = absint( $this->get_data( 'lesson' ) );
                return ( $saved_course_id === $given_course_id ) && ( $saved_lesson_id == $given_lesson_id );
                break;
        }

        return false;
    }

    /**
     * Get the element name
     *
     * @return string
     */
    public function get_name()
    {
        return __( 'LMS Action' , 'groundhogg');
    }

    /**
     * Get the description
     *
     * @return string
     */
    public function get_description()
    {
        return __('Automation for specific LMS based events.', 'groundhogg');
    }

    /**
     * Get the icon URL
     *
     * @return string
     */
    public function get_icon()
    {
        return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/lms.png';
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
                    'course_enrolled' => __('Course Enrolled', 'groundhogg'),
                    'course_completed' => __('Course Completed', 'groundhogg'),
                    'lesson_completed' => __('Lesson Completed', 'groundhogg'),
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
                'class' => 'gh-select2 auto-save'
            ])
        ]);

        html()->end_row();

        // COURSE
        html()->start_row();

        if ($this->get_setting('action') === 'lesson_complete'):

            html()->th([
                __('Lesson', 'groundhogg')
            ]);

            html()->td([
                html()->select2([
                    'id' => $this->setting_id_prefix('lesson'),
                    'name' => $this->setting_name_prefix('lesson'),
                    'data' => $this->get_lessons_for_select($course_id),
                    'selected' => $this->get_setting('lesson'),
                ])
            ]);

            html()->end_row();

        endif;

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
     * @param $course_id int the ID of the course
     * @return mixed
     */
    abstract protected function get_lessons_for_select( $course_id );

    /**
     * Save the step based on the given ID
     *
     * @param $step Step
     */
    public function save($step){
        $this->save_setting( 'action', sanitize_key( $this->get_posted_data( 'action' ) ) );
        $this->save_setting( 'course', absint( $this->get_posted_data( 'course' ) ) );
        $this->save_setting( 'lesson', absint( $this->get_posted_data( 'lesson' ) ) );
    }
}
