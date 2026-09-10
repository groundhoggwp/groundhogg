<?php

namespace Groundhogg\Abilities\Schemas;

use Groundhogg\Broadcast;
use function Groundhogg\percentage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared schema for a Groundhogg Broadcast, as returned by abilities like
 * groundhogg/list-broadcasts and groundhogg/get-broadcast.
 *
 * The standard fields (status, object, send time, audience size) are cheap. The
 * `stats` section runs a handful of COUNT queries over the events and activity
 * tables (via Broadcast::get_report_data()), so it is only included when
 * requested through the $include argument to transform() - same pattern as
 * Contact_Schema's `meta`.
 */
class Broadcast_Schema extends Schema {

	public static function get_schema(): array {

		return [
			'type'       => 'object',
			'properties' => [
				'id' => [
					'type' => 'integer',
				],
				'status' => [
					'type'        => 'string',
					'description' => __( 'pending / scheduled while events are still being enqueued, sending during delivery, sent when complete, cancelled if stopped.', 'groundhogg' ),
				],
				'object_type' => [
					'type'        => 'string',
					'description' => __( '"email" or "sms" - what is being broadcast.', 'groundhogg' ),
				],
				'object' => [
					'type'        => 'object',
					'description' => __( 'The email (or SMS) being sent.', 'groundhogg' ),
					'properties'  => [
						'id'    => [ 'type' => 'integer' ],
						'title' => [ 'type' => [ 'string', 'null' ] ],
					],
				],
				'send_time' => self::datetime_schema(),
				'scheduled_by' => [
					'type'        => 'integer',
					'description' => __( 'WordPress user ID that scheduled it - see groundhogg/list-owners.', 'groundhogg' ),
				],
				'segment_type' => [
					'type'        => 'string',
					'description' => __( '"fixed" (audience locked at schedule time) or "dynamic" (re-evaluated just before send).', 'groundhogg' ),
				],
				'is_scheduled' => [
					'type'        => 'boolean',
					'description' => __( 'True once every send event has been enqueued. False while a background task is still building the queue.', 'groundhogg' ),
				],
				'estimated_recipients' => [
					'type'        => 'integer',
					'description' => __( 'Audience size recorded when the broadcast was scheduled. See stats.sent for how many actually went out.', 'groundhogg' ),
				],
				'date_scheduled' => self::datetime_schema(),
				'stats' => [
					'type'        => 'object',
					'description' => __( 'Only present when "stats" is passed in expand. Zeros until the broadcast starts sending.', 'groundhogg' ),
					'properties'  => [
						'waiting'          => [
							'type'        => 'integer',
							'description' => __( 'Send events still queued.', 'groundhogg' ),
						],
						'sent'             => [ 'type' => 'integer' ],
						'opened'           => [ 'type' => 'integer' ],
						'clicked'          => [ 'type' => 'integer' ],
						'unsubscribed'     => [ 'type' => 'integer' ],
						'open_rate'        => [
							'type'        => 'number',
							'description' => __( 'opened / sent, as a percentage.', 'groundhogg' ),
						],
						'click_rate'       => [
							'type'        => 'number',
							'description' => __( 'clicked / sent, as a percentage.', 'groundhogg' ),
						],
						'unsubscribe_rate' => [
							'type'        => 'number',
							'description' => __( 'unsubscribed / sent, as a percentage.', 'groundhogg' ),
						],
					],
				],
			],
		];
	}

	/**
	 * Accepts an existing Broadcast, or anything Broadcast::__construct() accepts
	 * (an ID or raw DB row).
	 *
	 * @param Broadcast|int|object $object
	 * @param array                $include Optional sections: 'stats'.
	 *
	 * @return array
	 */
	public static function transform( $object, array $include = [] ): array {

		if ( ! $object instanceof Broadcast ) {
			$object = new Broadcast( $object );
		}

		$sendable = $object->get_object();
		$title    = ( $sendable && $sendable->exists() ) ? $sendable->get_title() : null;

		$data = [
			'id'                   => $object->get_id(),
			'status'               => $object->get_status(),
			'object_type'          => $object->get_broadcast_type(),
			'object'               => [
				'id'    => $object->get_object_id(),
				'title' => $title,
			],
			'send_time'            => self::datetime( $object->get_send_time() ),
			'scheduled_by'         => $object->get_scheduled_by_id(),
			'segment_type'         => (string) ( $object->get_meta( 'segment_type' ) ?: 'fixed' ),
			'is_scheduled'         => $object->is_scheduled(),
			'estimated_recipients' => absint( $object->get_meta( 'total_contacts' ) ),
			'date_scheduled'       => self::datetime( $object->get_date_scheduled() ),
		];

		if ( in_array( 'stats', $include, true ) ) {

			$report = $object->get_report_data();

			$sent    = intval( $report['sent'] ?? 0 );
			$opened  = intval( $report['opened'] ?? 0 );
			$clicked = intval( $report['clicked'] ?? 0 );
			$unsub   = intval( $report['unsubscribed'] ?? 0 );

			$data['stats'] = [
				'waiting'          => intval( $report['waiting'] ?? 0 ),
				'sent'             => $sent,
				'opened'           => $opened,
				'clicked'          => $clicked,
				'unsubscribed'     => $unsub,
				'open_rate'        => percentage( $sent, $opened ),
				'click_rate'       => percentage( $sent, $clicked ),
				'unsubscribe_rate' => percentage( $sent, $unsub ),
			];
		}

		return $data;
	}
}
