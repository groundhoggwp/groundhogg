<?php

namespace Groundhogg;

use Groundhogg\DB\Meta_DB;

/**
 * Created by PhpStorm.
 * User: atty
 * Date: 01-May-19
 * Time: 4:34 PM
 */
class Webhook extends Base_Object_With_Meta {

	protected static $requests = [];

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
	 */
	public function build_request( $event, $initiated_by, $data ) {

		$data = [
			'event'        => $event,
			'initiated_by' => $initiated_by,
			'data'         => $data
		];

		$headers = $this->get_meta( 'custom_headers' );

		$content_type = $this->get_meta( 'content_type' );

		switch ( $content_type ) {
			default:
			case 'json':
				$headers['Content-Type'] = sprintf( 'application/json; charset=%s', get_bloginfo( 'charset' ) );
				$data                    = wp_json_encode( $data );
				break;
			case 'form':
				$headers['Content-Type'] = sprintf( 'application/x-www-form-urlencoded; charset=%s', get_bloginfo( 'charset' ) );
				break;
		}

		$request = [
			'type'    => 'POST',
			'headers' => $headers,
			'url'     => $this->get_endpoint(),
			'data'    => $data,
			'options' => [],
		];

		self::$requests[] = $request;
	}

	/**
	 * Dispatch the webhooks for a particular endpoint
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
		 * Add the batch requests to the requests array
		 *
		 * @var $webhooks Webhook[]
		 */
		foreach ( $webhooks as $webhook ) {
			$webhook->build_request( $event, $initiated_by, $data );
		}

		// Initiate the requests
		\Requests::request_multiple( self::$requests );

		// Clear the requests
		self::$requests = [];
	}

	/**
	 * @inheritDoc
	 */
	protected function get_meta_db() {
		return get_db( 'webhook_meta' );
	}
}