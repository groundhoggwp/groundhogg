<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-10-09
 * Time: 10:49 AM
 */

class WPGH_Elements
{

    /**
     * Storage for the instances of the elements
     *
     * @var array
     */
    private $elements = array();


    function __construct()
    {
        $this->includes();

        /* actions */
        $this->elements[] = new WPGH_Admin_Notification();
        $this->elements[] = new WPGH_Apply_Note();
        $this->elements[] = new WPGH_Apply_Owner();
        $this->elements[] = new WPGH_Apply_Tag();
        $this->elements[] = new WPGH_Create_User();
        $this->elements[] = new WPGH_Date_Timer();
        $this->elements[] = new WPGH_Delay_Timer();
        $this->elements[] = new WPGH_Edit_Meta();
        $this->elements[] = new WPGH_HTTP_Post();
        $this->elements[] = new WPGH_Remove_Tag();
        $this->elements[] = new WPGH_Send_Email();

        /* Benchmarks */
        $this->elements[] = new WPGH_Account_Created();
        $this->elements[] = new WPGH_Email_Confirmed();
        $this->elements[] = new WPGH_Form_Filled();
        $this->elements[] = new WPGH_Page_Visited();
        $this->elements[] = new WPGH_Role_Changed();
        $this->elements[] = new WPGH_Tag_Applied();
        $this->elements[] = new WPGH_Tag_Removed();

    }

    /**
     * Include all the elements for the Funnel Steps
     */
    private function includes()
    {
        /* Parent Class */
        include_once dirname( __FILE__ ) . '/elements/class-wpgh-funnel-step.php';

        /* actions */
        $action_path = dirname( __FILE__ ) . '/elements/actions/';

        include_once $action_path . 'class-wpgh-admin-notification.php';
        include_once $action_path . 'class-wpgh-apply-note.php';
        include_once $action_path . 'class-wpgh-apply-owner.php';
        include_once $action_path . 'class-wpgh-apply-tag.php';
        include_once $action_path . 'class-wpgh-create-user.php';
        include_once $action_path . 'class-wpgh-date-timer.php';
        include_once $action_path . 'class-wpgh-delay-timer.php';
        include_once $action_path . 'class-wpgh-edit-meta.php';
        include_once $action_path . 'class-wpgh-http-post.php';
        include_once $action_path . 'class-wpgh-remove-tag.php';
        include_once $action_path . 'class-wpgh-send-email.php';

        /* Benchmarks */
        $benchmark_path = dirname( __FILE__ ) . '/elements/benchmarks/';

        include_once $benchmark_path . 'class-wpgh-account-created.php';
        include_once $benchmark_path . 'class-wpgh-email-confirmed.php';
        include_once $benchmark_path . 'class-wpgh-form-filled.php';
        include_once $benchmark_path . 'class-wpgh-page-visited.php';
        include_once $benchmark_path . 'class-wpgh-role-changed.php';
        include_once $benchmark_path . 'class-wpgh-tag-applied.php';
        include_once $benchmark_path . 'class-wpgh-tag-removed.php';

    }

    /**
     * Return an array of benchmarks
     *
     * @return
     */
    public function get_benchmarks()
    {
        return apply_filters( 'wpgh_funnel_actions', array() );
    }

    /**
     * Return an array of actions
     *
     * @return array
     */
    public function get_actions()
    {
        return apply_filters( 'wpgh_funnel_benchmarks', array() );
    }

    /**
     * Get an array of ALL benchmarks and actions
     *
     * @return array
     */
    public function get_elements()
    {

        return array_merge( $this->get_actions(), $this->get_benchmarks() );

    }



}