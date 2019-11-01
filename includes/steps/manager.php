<?php
namespace Groundhogg\Steps;
use Groundhogg\Steps\Actions\Action;
use Groundhogg\Steps\Actions\Admin_Notification;
use Groundhogg\Steps\Actions\Advanced_Timer;
use Groundhogg\Steps\Actions\Apply_Note;
use Groundhogg\Steps\Actions\Apply_Owner;
use Groundhogg\Steps\Actions\Apply_Tag;
use Groundhogg\Steps\Actions\Create_User;
use Groundhogg\Steps\Actions\Date_Timer;
use Groundhogg\Steps\Actions\Delay_Timer;
use Groundhogg\Steps\Actions\Edit_Meta;
use Groundhogg\Steps\Actions\Field_Timer;
use Groundhogg\Steps\Actions\HTTP_Post;
use Groundhogg\Steps\Actions\Remove_Tag;
use Groundhogg\Steps\Actions\Send_Email;
use Groundhogg\Steps\Actions\Sleep;
use Groundhogg\Steps\Benchmarks\Account_Created;
use Groundhogg\Steps\Benchmarks\Plugin_Api;
use Groundhogg\Steps\Benchmarks\Benchmark;
use Groundhogg\Steps\Benchmarks\Email_Confirmed;
use Groundhogg\Steps\Benchmarks\Form_Filled;
use Groundhogg\Steps\Benchmarks\Link_Clicked;
use Groundhogg\Steps\Benchmarks\Login_Status;
use Groundhogg\Steps\Benchmarks\Page_Visited;
use Groundhogg\Steps\Benchmarks\Role_Changed;
use Groundhogg\Steps\Benchmarks\Tag_Applied;
use Groundhogg\Steps\Benchmarks\Tag_Removed;
use function Groundhogg\is_option_enabled;

/**
 * Created by PhpStorm.
 * User: atty
 * Date: 01-May-19
 * Time: 4:34 PM
 */

class Manager {

    /**
     * Storage for the instances of the elements
     *
     * @var array
     */
    private $elements = array();

    /**
     * Manager constructor.
     */
    public function __construct()
    {
        // RIGHT AFTER THE DBS.
        add_action( 'setup_theme', [ $this, 'init_steps' ], 2 );
    }

    public function init_steps()
    {
        /* actions */
        $this->elements[] = new Send_Email();
        $this->elements[] = new Admin_Notification();
        $this->elements[] = new Apply_Tag();
        $this->elements[] = new Remove_Tag();
        $this->elements[] = new Apply_Note();
        $this->elements[] = new Delay_Timer();

        /* Benchmarks */
        $this->elements[] = new Account_Created();
        $this->elements[] = new Form_Filled();
        $this->elements[] = new Email_Confirmed();
        $this->elements[] = new Link_Clicked();

        // No page view if tracking is disabled.
        $this->elements[] = new Tag_Applied();
        $this->elements[] = new Tag_Removed();

        /* Other */
        $this->elements[] = new Error();

        do_action( 'groundhogg/steps/init', $this );
    }

    /**
     * @param $step Funnel_Step
     */
    public function add_step( $step )
    {
        $this->elements[] = $step;
    }

    /**
     * Return an array of benchmarks
     *
     * @return Benchmark[]
     */
    public function get_benchmarks()
    {
        return apply_filters( "groundhogg/steps/benchmarks", array() );
    }

    /**
     * Return an array of actions
     *
     * @return Action[]
     */
    public function get_actions()
    {
        return apply_filters( 'groundhogg/steps/actions',  array() );
    }

    /**
     * Get an array of ALL benchmarks and actions
     *
     * @return Funnel_Step[]
     */
    public function get_elements()
    {
        return array_merge( $this->get_actions(), $this->get_benchmarks() );
    }
}