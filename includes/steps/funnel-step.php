<?php

namespace Groundhogg\Steps;

use Groundhogg\Contact;
use function Groundhogg\get_db;
use function Groundhogg\get_post_var;
use Groundhogg\Event;
use function Groundhogg\get_array_var;
use Groundhogg\Step;
use Groundhogg\Supports_Errors;
use function Groundhogg\isset_not_empty;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Funnel Step Parent
 *
 * Provides an easy way to add new funnel steps to the funnel builder.
 * Just extend this class and overwrite the following functions
 *
 * save()
 * run()
 *
 * if it's a benchmark, make a call to __construct() and add the function
 *
 * complete()
 *
 * @since       File available since Release 0.9
 * @see         WPGH_Form_Filled for an example.
 *
 * @subpackage  Elements/Benchmarks
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Elements
 */
abstract class Funnel_Step extends Supports_Errors {

	protected static $step_properties = [];

	/**
	 * The current step
	 *
	 * @var Step
	 */
	protected $current_step = null;
	protected $current_contact = null;

	/**
	 * @var array
	 */
	protected $posted_settings = [];

	const ACTION = 'action';
	const BENCHMARK = 'benchmark';

	/**
	 * Setup all of the filters and actions to register this step and save it.
	 *
	 * WPGH_Funnel_Step constructor.
	 */
	public function __construct() {

		add_filter( "groundhogg/steps/{$this->get_group()}s", [ $this, 'register' ] );

		add_action( "groundhogg/steps/{$this->get_type()}/save", [ $this, 'pre_save' ], 1, 2 );
		add_action( "groundhogg/steps/{$this->get_type()}/save", [ $this, 'save' ], 11, 2 );
		add_action( "groundhogg/steps/{$this->get_type()}/save", [ $this, 'after_save' ], 99, 2 );

		add_action( "groundhogg/steps/{$this->get_type()}/import", [ $this, 'pre_import' ], 1, 2 );
		add_action( "groundhogg/steps/{$this->get_type()}/import", [ $this, 'import' ], 10, 2 );
		add_filter( "groundhogg/steps/{$this->get_type()}/export", [ $this, 'pre_export' ], 1, 2 );
		add_filter( "groundhogg/steps/{$this->get_type()}/export", [ $this, 'export' ], 10, 2 );
		add_filter( "groundhogg/steps/{$this->get_type()}/enqueue", [ $this, 'pre_enqueue' ], 1 );
		add_filter( "groundhogg/steps/{$this->get_type()}/enqueue", [ $this, 'enqueue' ] );
		add_filter( "groundhogg/steps/{$this->get_type()}/run", [ $this, 'pre_run' ], 1, 2 );
		add_filter( "groundhogg/steps/{$this->get_type()}/run", [ $this, 'run' ], 10, 2 );
		add_filter( "groundhogg/steps/{$this->get_type()}/icon", [ $this, 'get_icon' ] );
		add_action( "wp_enqueue_scripts", [ $this, 'frontend_scripts' ] );

		$this->add_additional_actions();

	}

	public static function step_props(){
		return self::$step_properties;
	}

	protected function add_additional_actions() {
	}

	/**
	 * Get the element name
	 *
	 * @return string
	 */
	abstract public function get_name();

	/**
	 * Get the element type
	 *
	 * @return string
	 */
	abstract public function get_type();

	/**
	 * Get the element group
	 *
	 * @return string
	 */
	abstract public function get_group();

	/**
	 * Get the description
	 *
	 * @return string
	 */
	abstract public function get_description();

	/**
	 * Get the icon URL
	 *
	 * @return string
	 */
	abstract public function get_icon();

	/**
	 * Get the ICON of this action/benchmark
	 *
	 * @return string
	 */
	protected function get_default_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/no-icon.png';
	}

	/**
	 * Register the this action/benchmark with the globals...
	 *
	 * @param $array
	 *
	 * @return mixed
	 */
	public function register( $array ) {
		$array[ $this->get_type() ] = $this;

		self::$step_properties[ $this->get_type() ] = [
			'type'        => $this->get_type(),
			'name'        => $this->get_name(),
			'description' => $this->get_description(),
			'icon'        => $this->get_icon(),
			'group'       => $this->get_group(),
		];

		return $array;
	}

	/**
	 * Retrieves a setting from the settings array provide by the step meta.
	 *
	 * @param bool $default
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	protected function get_setting( $key = '', $default = false ) {
		$val = $this->get_current_step()->get_meta( $key );

		return $val ? $val : $default;
	}

	/**
	 * Update a setting.
	 *
	 * @param string $val
	 *
	 * @param string $setting
	 */
	protected function save_setting( $setting = '', $val = '' ) {
		if ( empty( $val ) ) {
			$this->get_current_step()->delete_meta( $setting );
		} else {
			$this->get_current_step()->update_meta( $setting, $val );
		}
	}

	/**
	 * Get a normalized array of data for saving the step.
	 *
	 * @return array
	 *
	 * @deprecated
	 */
	protected function get_posted_settings() {
		return $this->posted_settings;
	}

	/**
	 * Retrieves a setting from the posted settings when saving.
	 *
	 * @param bool $default
	 *
	 * @param string $key
	 *
	 * @return mixed
	 *
	 */
	protected function get_posted_data( $key = '', $default = false ) {
		return get_array_var( $this->posted_settings, $key, $default );
	}

	/**
	 * @return Step
	 */
	public function get_current_step() {
		return $this->current_step;
	}

	/**
	 * @param Step $step
	 */
	protected function set_current_step( Step $step ) {
		$this->current_step = $step;
	}

	/**
	 * @param Contact $contact
	 */
	protected function set_current_contact( Contact $contact ) {
		$this->current_contact = $contact;
	}

	/**
	 * @return Contact
	 */
	protected function get_current_contact() {
		return $this->current_contact;
	}

	/**
	 * Initialize the posted settings array
	 *
	 * @param $step Step
	 * @param $settings mixed[]
	 */
	public function pre_save( Step $step, $settings ) {

		$this->set_current_step( $step );
		$this->posted_settings = $settings;

		$step->update_meta( 'step_notes', sanitize_textarea_field( get_post_var( 'notes' ) ) );
	}

	/**
	 * Save the step based on the given ID
	 *
	 * @param $step Step
	 * @param $settings
	 */
	abstract public function save( $step, $settings );

	/**
	 * @param $step Step
	 * @param $settings mixed[]
	 */
	public function after_save( $step, $settings ) {
		do_action( 'groundhogg/steps/save/after', $this, $step );
	}

	/**
	 * Get similar steps which can be used by benchmarks.
	 * @return Step[]
	 */
	public function get_like_steps( $query = [] ) {

		$args  = [ 'step_type' => $this->get_type(), 'step_group' => $this->get_group() ];
		$query = array_merge( $query, $args );

		$raw_steps = get_db( 'steps' )->query( $query );

		$steps = [];

		foreach ( $raw_steps as $raw_step ) {
			$step = new Step( absint( $raw_step->ID ) );

			if ( $step ) {
				$steps[] = $step;
			}
		}

		return $steps;

	}

	/**
	 * Setup args before the action/benchmark is run
	 *
	 * @param $contact Contact
	 * @param $event   Event
	 *
	 * @return Contact
	 */
	public function pre_run( $contact, $event ) {
		$this->set_current_step( $event->get_step() );
		$this->set_current_contact( $contact );

		return $contact;
	}

	/**
	 * Run the action/benchmark
	 *
	 * @param $contact Contact
	 * @param $event   Event
	 *
	 * @return bool
	 */
	public function run( $contact, $event ) {
		return true;
	}

	/**
	 * @param $step
	 *
	 * @return Step
	 */
	public function pre_enqueue( $step ) {
		$this->set_current_step( $step );

		return $step;
	}

	/**
	 * @param $step
	 */
	public function pre_import( $args, $step ) {
		$this->set_current_step( $step );
	}

	/**
	 * @param $args array of args
	 * @param $step Step
	 */
	public function import( $args, $step ) {
		//silence is golden
	}

	/**
	 * @param $args
	 * @param $step
	 *
	 * @return array
	 */
	public function pre_export( $args, $step ) {
		$this->set_current_step( $step );

		return $args;
	}

	/**
	 * @param $args array of args
	 * @param $step Step
	 *
	 * @return array
	 */
	public function export( $args, $step ) {
		//silence is golden
		return $args;
	}
}