<?php

namespace Groundhogg\Steps\Benchmarks;

use Groundhogg\Classes\Activity;
use Groundhogg\Contact;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class Activity_Benchmark extends Benchmark {

	public $type = '';
	public $name = '';
	public $description = '';
	public $icon = '';
	public $callbacks = [];

	public function __construct( $props ) {

		$props = wp_parse_args( $props, [
			'name'        => '',
			'type'        => '',
			'description' => '',
			'icon'        => '',
		] );

		$this->type        = $props['type'];
		$this->name        = $props['name'];
		$this->icon        = $props['icon'];
		$this->description = $props['description'];

		parent::__construct();
	}

	protected function get_complete_hooks() {
		return [
			"groundhogg/track_activity/{$this->type}" => 2
		];
	}

	/**
	 * Handle the activity
	 *
	 * @param $activity Activity
	 * @param $contact  Contact
	 *
	 * @return void
	 */
	public function setup( $activity, $contact ) {

		// The given activity does not match this type
		if ( $activity->type !== $this->type ) {
			return;
		}

		$this->add_data( 'activity', $activity );
		$this->set_current_contact( $contact );
	}

	/**
	 * The current contact
	 *
	 * @return Contact
	 */
	protected function get_the_contact() {
		return $this->get_current_contact();
	}

	/**
	 * Get the current activity record
	 *
	 * @return Activity
	 */
	protected function get_current_activity() {
		return $this->get_data( 'activity' );
	}

	public function get_name() {
		return $this->name;
	}

	public function get_type() {
		// prefix with underscore to avoid conflicts
		return '_' . $this->type;
	}

	public function get_description() {
		return $this->description;
	}

	public function get_icon() {
		return $this->icon;
	}
}
