<?php

namespace Groundhogg;

/**
 * Created by PhpStorm.
 * User: atty
 * Date: 01-May-19
 * Time: 4:34 PM
 */
class Webhook extends Base_Object {

	/**
	 * @return DB\DB|DB\Meta_DB|DB\Tags
	 */
	protected function get_db() {
		return get_db( 'webhooks' );
	}

	/**
	 * @return string
	 */
	protected function get_object_type() {
		return 'webhook';
	}

	/**
	 * Do any post setup actions.
	 *
	 * @return void
	 */
	protected function post_setup() {
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
	 * Dispatch the webhook
	 *
	 * @param $event string
	 * @param $initiated_by string
	 * @param $data  mixed
	 *
	 * @return array|bool|object|\WP_Error
	 */
	public function post( $event, $initiated_by, $data ) {

		$data = [
			'event'        => $event,
			'initiated_by' => $initiated_by,
			'data'         => $data
		];

		return remote_post_json( $this->get_endpoint(), $data );
	}

	/**
	 * Dispatch the webhooks for a particular endpoint
	 *
	 * todo introduce batching HTTP requests...
	 *
	 * @param $event
	 * @param $initiated_by
	 * @param $data
	 */
	public static function dispatch( $event, $initiated_by, $data ) {

		$query = [
			'where' => [
				[ 'events', 'RLIKE', $event ],
				[ 'initiation', 'RLIKE', $initiated_by ],
			]
		];

		$webhooks = wp_list_pluck( get_db( 'webhooks' )->query( $query ), 'ID' );
		$webhooks = id_list_to_class( $webhooks, Webhook::class );

		/**
		 * @var $webhooks Webhook[]
		 */
		foreach ( $webhooks as $webhook ) {
			$webhook->post( $event, $initiated_by, $data );
		}

	}
}