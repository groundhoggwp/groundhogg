<?php

namespace Groundhogg;

/**
 * Created by PhpStorm.
 * User: atty
 * Date: 01-May-19
 * Time: 4:34 PM
 */
class Webhook extends Base_Object {

	protected function get_db() {
		return get_db( 'webhooks' );
	}

	protected function get_object_type() {
		return 'webhook';
	}

	/**
	 * Do any post setup actions.
	 *
	 * @return void
	 */
	protected function post_setup() {
		$this->events = maybe_unserialize( $this->events );
	}

	/**
	 * @param array $data
	 *
	 * @return array
	 */
	protected function sanitize_columns( $data = [] ) {
		map_func_to_attr( $data, 'events', 'maybe_serialize' );

		return $data;
	}

	/**
	 * The endpoint URL
	 *
	 * @return string
	 */
	public function get_endpoint() {
		return $this->endpoint;
	}

	/**
	 * The list of events this webhook is subscribed to.
	 *
	 * @return bool|mixed
	 */
	public function get_events() {
		return $this->events;
	}

	/**
	 * Subscribe to all of the events.
	 *
	 * Events have the following structure
	 */
	public function subscribe() {

		$events = $this->get_events();

		foreach ( $events as $event ) {

			// Ignore non registered event types...
			if ( ! isset_not_empty( self::$event_types, $event ) ) {
				continue;
			}

			add_action( "groundhogg/{$event}", [ $this, 'dispatch' ], 10, 3 );
		}
	}

	/**
	 * Enqueue the webhook into the scheduler
	 *
	 * @param $object      Base_Object_With_Meta|Base_Object
	 * @param $event       string
	 */
	public function dispatch( $event, $data ) {

		enqueue_event( [
			'event_type' => Event::WEBHOOK,
			'step_id'    => $this->get_id(), // use step_id for the webhook ID
			'data'       => [
				'event' => $event,

			],
		] );

	}

	/**
	 * Post the base object to the URL endpoint in question.
	 *
	 * @param mixed  $data
	 * @param string $event
	 *
	 * @return true|\WP_Error
	 */
	public function post( $data, $event = '' ) {

		$data = [
			'event' => $event,
			'data'  => $data
		];

		$result = remote_post_json( $this->get_endpoint(), $data );

		return is_wp_error( $result ) ? $result : true;
	}

	/**
	 * subscribe all the webhooks to their events
	 */
	public static function init() {

		$webhooks = get_db( 'webhooks' )->query( [ 'status' => 'active' ] );

		foreach ( $webhooks as $webhook ) {
			$webhook = new Webhook( $webhook->ID );

			$webhook->subscribe();
		}
	}

	/**
	 * Holds the registered event types...
	 *
	 * @var array
	 */
	public static $event_types = [];

	/**
	 * Register and event type
	 *
	 * @param $type
	 * @param $callback
	 */
	public static function register_event_type( $type, $callback ) {
		self::$event_types[ $type ] = [
			'callback' => $callback
		];
	}

	/**
	 * Register all the default event types
	 *
	 * @type callable callback
	 */
	public static function register_default_event_types() {

		$event_types = [
			[
				'type'                   => 'contact/created',
				'post_data_callback'     => function () {

				},
				'dispatch_data_callback' => function () {

				}
			],
		];

		foreach ( $event_types as $event_type ) {
			self::register_event_type( $event_type['type'], $event_type['callback'] );
		}
	}

	/**
	 * Event callback for the email notification
	 *
	 * @param $event Event
	 *
	 * @return bool
	 */
	public static function event_callback( $event ) {

		$webhook = new Webhook( $event->get_step_id() );

		$event_data = $event->get_event_data();

		$event = get_array_var( $event_data, 'event' );


		return $webhook->post( '' , $event );
	}
}