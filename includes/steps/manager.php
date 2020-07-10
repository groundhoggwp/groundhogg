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
	public $elements = array();

	/**
	 * Manager constructor.
	 */
	public function __construct() {
		// RIGHT AFTER THE DBS.
		add_action( 'setup_theme', [ $this, 'init_steps' ], 2 );
	}

	/**
	 * Initialize all the steps
	 */
	public function init_steps() {

//		if ( ! empty( $this->elements ) ){
//			return;
//		}


		/* actions */
		$this->add_step( new Send_Email() );
		$this->add_step( new Admin_Notification() );
		$this->add_step( new Apply_Tag() );
		$this->add_step( new Remove_Tag() );
		$this->add_step( new Apply_Note() );
		$this->add_step( new Delay_Timer() );

		/* Benchmarks */

		$this->add_step( new Form_Filled() );
		$this->add_step( new Account_Created() );
		$this->add_step( new Email_Confirmed() );
		$this->add_step( new Link_Clicked() );
		$this->add_step( new Tag_Applied() );
		$this->add_step( new Tag_Removed() );

		/* Other */
		$this->add_step( new Error() );

		do_action( 'groundhogg/steps/init', $this );
	}

	/**
	 * @param $step Funnel_Step
	 */
	public function add_step( $step ) {
		$this->elements[ $step->get_type() ] = $step;
	}

	/**
	 * Return an array of benchmarks
	 *
	 * @return Benchmark[]
	 */
	public function get_benchmarks() {
		return apply_filters( "groundhogg/steps/benchmarks", array() );
	}

	/**
	 * Return an array of actions
	 *
	 * @return Action[]
	 */
	public function get_actions() {
		return apply_filters( 'groundhogg/steps/actions', array() );
	}

	public function get_actions_as_array() {
		$actions = $this->get_actions();

		$array = [];

		foreach ( $actions as $action ) {
			$array[] = [
				'icon'        => $action->get_icon(),
				'type'        => $action->get_type(),
				'group'       => $action->get_group(),
				'name'        => $action->get_name(),
				'description' => $action->get_description(),
			];
		}

		return $array;
	}


	public function get_benchmarks_as_array() {
		$benchmarks = $this->get_benchmarks();

		$array = [];

		foreach ( $benchmarks as $benchmark ) {
			$array[] = [
				'icon'        => $benchmark->get_icon(),
				'type'        => $benchmark->get_type(),
				'group'       => $benchmark->get_group(),
				'name'        => $benchmark->get_name(),
				'description' => $benchmark->get_description(),
			];
		}

		return $array;
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
}