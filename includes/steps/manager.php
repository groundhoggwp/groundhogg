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
use Groundhogg\Steps\Benchmarks\Account_Created;
use Groundhogg\Steps\Benchmarks\Benchmark;
use Groundhogg\Steps\Benchmarks\Email_Confirmed;
use Groundhogg\Steps\Benchmarks\Form_Filled;
use Groundhogg\Steps\Benchmarks\Link_Clicked;
use Groundhogg\Steps\Benchmarks\Login_Status;
use Groundhogg\Steps\Benchmarks\Page_Visited;
use Groundhogg\Steps\Benchmarks\Plugin_Api;
use Groundhogg\Steps\Benchmarks\Role_Changed;
use Groundhogg\Steps\Benchmarks\Tag_Applied;
use Groundhogg\Steps\Benchmarks\Tag_Removed;
use Groundhogg\Steps\Benchmarks\Web_Form;
use function Groundhogg\get_array_var;

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
	 * @var Funnel_Step[]
	 */
	public $elements = [];
	public $sub_groups = [];

	/**
	 * Manager constructor.
	 */
	public function __construct() {
		// RIGHT AFTER THE DBS.
		add_action( 'setup_theme', [ $this, 'init_steps' ], 2 );
	}


	/**
	 * Register a new group
	 *
	 * @param $group
	 * @param $name
	 */
	public function register_sub_group( $group, $name, $priority = 10 ) {
		$this->sub_groups[ $group ] = $name;
	}

	/**
	 * Initialize all the steps
	 */
	public function init_steps() {

		$this->register_sub_group( 'delay', __( 'Delay' ) );
		$this->register_sub_group( 'comms', __( 'Communications' ) );
		$this->register_sub_group( 'notifications', __( 'Notifications' ) );
		$this->register_sub_group( 'forms', __( 'Forms' ) );
		$this->register_sub_group( 'activity', __( 'Activity' ) );
		$this->register_sub_group( 'crm', __( 'CRM' ) );
		$this->register_sub_group( 'wordpress', __( 'WordPress' ) );
		$this->register_sub_group( 'sms', __( 'SMS' ) );
		$this->register_sub_group( 'user', __( 'User' ) );
		$this->register_sub_group( 'lms', __( 'LMS' ) );
		$this->register_sub_group( 'other', __( 'Other' ) );
		$this->register_sub_group( 'developer', __( 'Developer' ) );

		/* actions */
		$this->add_step( new Send_Email() );
		$this->add_step( new Admin_Notification() );
		$this->add_step( new Apply_Tag() );
		$this->add_step( new Remove_Tag() );
		$this->add_step( new Apply_Note() );
		$this->add_step( new Delay_Timer() );

		/* Benchmarks */

		$this->add_step( new Web_Form() );
		$this->add_step( new Account_Created() );
		$this->add_step( new Email_Confirmed() );
		$this->add_step( new Link_Clicked() );
		$this->add_step( new Tag_Applied() );
		$this->add_step( new Tag_Removed() );
		$this->add_step( new Form_Filled() );

		/* Other */
		$this->add_step( new Error() );

		do_action( 'groundhogg/steps/init', $this );

		// Order by subgroup
	}

	public function __set( $name, $value ) {
		if ( method_exists( $value, 'get_type' ) ) {
			$this->add_step( $value );
		}
	}

	/**
	 * @param $step Funnel_Step
	 */
	public function add_step( $step ) {
		$this->elements[ $step->get_type() ] = $step;
	}

	function filter_by_group( $group ){
		return array_filter( $this->elements, function ( $element ) use ( $group ) {
			return $element->get_group() === $group;
		} );
	}

	function filter_by_sub_group( $group ){
		return array_filter( $this->elements, function ( $element ) use ( $group ) {
			return $element->get_sub_group() === $group;
		} );
	}

	/**
	 * Return an array of benchmarks
	 *
	 * @return Benchmark[]
	 */
	public function get_benchmarks() {
		return array_filter( $this->elements, function ( $element ) {
			return $element->get_group() === Funnel_Step::BENCHMARK;
		} );
	}

	/**
	 * Return an array of actions
	 *
	 * @return Action[]
	 */
	public function get_actions() {
		return array_filter( $this->elements, function ( $element ) {
			return $element->get_group() === Funnel_Step::ACTION;
		} );
	}

	/**
	 * @return array
	 */
	public function get_benchmark_types() {
		$types = [];

		foreach ( $this->get_benchmarks() as $benchmark ) {
			$types[] = $benchmark->get_type();
		}

		return $types;
	}

	/**
	 * @return array
	 */
	public function get_action_types() {
		$types = [];

		foreach ( $this->get_actions() as $action ) {
			$types[] = $action->get_type();
		}

		return $types;
	}

	/**
	 * Get an array of ALL benchmarks and actions
	 *
	 * @return Funnel_Step[]
	 */
	public function get_elements() {
		return array_merge( $this->get_actions(), $this->get_benchmarks() );
	}

	/**
	 * Whether a specific step type is registered
	 *
	 *
	 * @param $step_type
	 *
	 * @return bool
	 */
	public function type_is_registered( $step_type ) {
		return in_array( $step_type, array_keys( $this->elements ) );
	}

	/**
	 * @param $get_type
	 *
	 * @return Funnel_Step
	 */
	public function get_element( $get_type ) {

		if ( ! $this->type_is_registered( $get_type ) ) {
			return $this->get_element( 'error' );
		}

		return get_array_var( $this->elements, $get_type );

	}
}
