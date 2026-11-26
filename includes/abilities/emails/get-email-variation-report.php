<?php

namespace Groundhogg\Abilities\Emails;

use Groundhogg\Abilities\Ability;
use Groundhogg\Email;
use Groundhogg\Email_Variations;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * How an email and its versions did, and for an A/B test, which version is ahead and how sure that is. See
 * Email_Variations::report().
 */
class Get_Email_Variation_Report extends Ability {

	protected const NAME       = 'groundhogg/get-email-variation-report';
	protected const CATEGORY   = 'groundhogg-email';
	protected const CAPABILITY = 'view_emails';

	protected const READONLY   = true;
	protected const IDEMPOTENT = true;

	protected function get_args(): array {

		$count = [ 'type' => 'integer' ];
		$rate  = [
			'type'        => 'number',
			'description' => __( 'Out of 1, of what was sent.', 'groundhogg' ),
		];

		return [
			'label'       => __( 'Get Email Variation Report', 'groundhogg' ),
			'description' => __( 'How an email and each of its versions did: sent, opened, clicked and unsubscribed, from when its A/B test started. For a test, which version is ahead on clicks (or opens), how confident that is, and the suggested winner once it\'s 95% sure. Declare the winner with groundhogg/declare-email-winner. Opens are less reliable than clicks, since some email apps open every email to protect privacy.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'email_id' ],
				'properties'           => [
					'email_id'     => [
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => __( 'The email with the versions, not one of them.', 'groundhogg' ),
					],
					'metric'       => [
						'type'    => 'string',
						'enum'    => Email_Variations::METRICS,
						'default' => 'clicks',
					],
					'since'        => [
						'type'        => 'string',
						'format'      => 'date',
						'description' => __( 'Count from this date (Y-m-d) instead of from when the test started.', 'groundhogg' ),
					],
					'broadcast_id' => [
						'type'        => 'integer',
						'description' => __( 'Only what this broadcast sent.', 'groundhogg' ),
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'metric'           => [ 'type' => 'string' ],
					'since'            => [
						'type'        => 'string',
						'description' => __( 'When counting started, Y-m-d H:i:s in UTC. Empty when everything is counted.', 'groundhogg' ),
					],
					'winner'           => [
						'type'        => 'integer',
						'description' => __( 'The declared winner\'s email ID, or 0 while the test is running.', 'groundhogg' ),
					],
					'leader'           => [
						'type'        => 'integer',
						'description' => __( 'The email ID of the version ahead in the test.', 'groundhogg' ),
					],
					'confidence'       => [
						'type'        => 'number',
						'description' => __( 'How sure it is that the leader really does better than the next best, out of 1.', 'groundhogg' ),
					],
					'suggested_winner' => [
						'type'        => 'integer',
						'description' => __( 'The leader once the confidence is 0.95 or more, otherwise 0: keep the test running.', 'groundhogg' ),
					],
					'broadcast_test'   => [
						'type'        => 'object',
						'description' => __( 'A broadcast that tests the email on a sample first, see groundhogg/send-email-broadcast\'s ab_test: its status (testing, done or failed), the sample\'s broadcast, when the winner is sent, and the winner.', 'groundhogg' ),
					],
					'versions'         => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'id'           => [ 'type' => 'integer' ],
								'title'        => [ 'type' => 'string' ],
								'kind'         => [
									'type'        => 'string',
									'enum'        => [ 'email', 'test', 'audience' ],
									'description' => __( '"email": the email itself, which is version A of a test. "test": a test version. "audience": a version for an audience, like a language, which isn\'t in the test.', 'groundhogg' ),
								],
								'status'       => [ 'type' => 'string' ],
								'weight'       => [
									'type'        => 'integer',
									'description' => __( 'Its share of the test, in percent, as it\'s set. A draft test version gets none until it\'s ready, and the email gets its share meanwhile.', 'groundhogg' ),
								],
								'sent'         => $count,
								'opened'       => $count,
								'clicked'      => $count,
								'unsubscribed' => $count,
								'open_rate'    => $rate,
								'click_rate'   => $rate,
							],
						],
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$email = new Email( absint( $input['email_id'] ) );

		if ( ! $email->exists() ) {
			return new WP_Error( 'groundhogg_email_not_found', __( 'Email not found.', 'groundhogg' ) );
		}

		if ( $email->is_variation() ) {
			return new WP_Error( 'groundhogg_is_variation', sprintf(
				/* translators: %d: the ID of the email the variation belongs to */
				__( 'This is a variation. Get the report of the email it\'s a variation of instead, email_id %d.', 'groundhogg' ),
				$email->get_parent_id()
			) );
		}

		$args = [
			'metric'       => $input['metric'] ?? 'clicks',
			'broadcast_id' => absint( $input['broadcast_id'] ?? 0 ),
		];

		if ( ! empty( $input['since'] ) ) {

			$since = strtotime( (string) $input['since'] );

			if ( ! $since ) {
				return new WP_Error( 'groundhogg_invalid_date', __( '`since` has to be a date like 2026-11-01.', 'groundhogg' ) );
			}

			$args['since'] = $since;
		}

		$report = Email_Variations::report( $email, $args );

		$report['since'] = $report['since'] ? gmdate( 'Y-m-d H:i:s', $report['since'] ) : '';

		$test = $email->get_meta( Email_Variations::BROADCAST_TEST_KEY );

		if ( is_array( $test ) ) {
			$report['broadcast_test'] = array_filter( [
				'status'             => $test['status'] ?? '',
				'broadcast_id'       => absint( $test['broadcast_id'] ?? 0 ),
				'winner_sent_at'     => empty( $test['finish_at'] ) ? '' : gmdate( 'Y-m-d H:i:s', absint( $test['finish_at'] ) ),
				'sample'             => absint( $test['sample'] ?? 0 ),
				'rest'               => absint( $test['rest'] ?? 0 ),
				'winner'             => absint( $test['winner'] ?? 0 ),
				'winner_broadcast_id' => absint( $test['rest_broadcast_id'] ?? 0 ),
				'error'              => (string) ( $test['error'] ?? '' ),
			], function ( $value ) {
				return $value !== '' && $value !== 0;
			} );
		}

		return $report;
	}
}
