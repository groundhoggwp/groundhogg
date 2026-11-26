<?php

namespace Groundhogg\Abilities\Emails;

use Groundhogg\Abilities\Ability;
use Groundhogg\Abilities\Schemas\Email_Schema;
use Groundhogg\Abilities\Schemas\Segment_Schema;
use Groundhogg\Email;
use Groundhogg\Email_Variations;
use Groundhogg\Multilingual;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add a variation to an email: another version of it, like the email in another language, that's sent instead of it to
 * the contacts in the variation's audience. See Email_Variations.
 *
 * By default the variation starts as a copy of the email with the same block ids, so an agent translating it can
 * change the text block by block with groundhogg/edit-email.
 */
class Create_Email_Variation extends Ability {

	protected const NAME       = 'groundhogg/create-email-variation';
	protected const CATEGORY   = 'groundhogg-email';
	protected const CAPABILITY = 'add_emails';

	protected const READONLY    = false;
	protected const DESTRUCTIVE = false;
	protected const IDEMPOTENT  = false;

	/**
	 * The `audience` input, shared with groundhogg/update-email-variation
	 *
	 * @return array
	 */
	public static function audience_input_schema(): array {

		$properties = Segment_Schema::properties();

		// A variation is for whoever is sent the email, these name particular contacts or can't be stored as filters
		unset( $properties['search'], $properties['include'], $properties['exclude'] );

		return [
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => $properties,
			'description'          => __( 'Who gets the variation instead of the email, in the same shape as a segment in groundhogg/search-contacts. For a translation that is just `locales`, like {"locales": ["fr_FR", "fr_CA"]}; see groundhogg/get-setup-status for the site\'s languages. Everything given must match.', 'groundhogg' ),
		];
	}

	/**
	 * Turn an `audience` input into what a variation stores
	 *
	 * @param array $input
	 *
	 * @return array|WP_Error
	 */
	public static function audience_from_input( array $input ) {

		$locales = [];

		if ( ! empty( $input['locales'] ) ) {

			$locales = array_values( array_filter( array_map( [ Multilingual::class, 'sanitize_locale' ], (array) $input['locales'] ) ) );

			if ( empty( $locales ) ) {
				return new WP_Error( 'groundhogg_invalid_locale', __( 'None of the locales are WordPress locales. Use locales like fr_FR or de_DE.', 'groundhogg' ) );
			}
		}

		// The languages are checked without a query when the email is sent, so they're kept apart from the filters
		$segment = $input;
		unset( $segment['locales'], $segment['search'], $segment['include'], $segment['exclude'], $segment['exclude_segment'] );

		$include = Segment_Schema::to_filters( $segment );

		if ( is_wp_error( $include ) ) {
			return $include;
		}

		$exclude = [];

		if ( ! empty( $input['exclude_segment'] ) && is_array( $input['exclude_segment'] ) ) {

			$exclude = Segment_Schema::to_filters( $input['exclude_segment'] );

			if ( is_wp_error( $exclude ) ) {
				return $exclude;
			}
		}

		$audience = Email_Variations::sanitize_audience( [
			'locales'         => $locales,
			'include_filters' => $include,
			'exclude_filters' => $exclude,
		] );

		if ( Email_Variations::audience_is_empty( $audience ) ) {
			return new WP_Error( 'groundhogg_no_audience', __( 'Say who gets the variation, like {"locales": ["fr_FR"]}. A variation with no audience is never sent.', 'groundhogg' ) );
		}

		return $audience;
	}

	protected function get_args(): array {

		return [
			'label'       => __( 'Create Email Variation', 'groundhogg' ),
			'description' => __( 'Add another version of an email: one for an audience, like the email in another language, that is sent instead of it to the contacts in that audience, or a test version for an A/B test, which gets a share of everyone else. Flows, broadcasts and notifications keep sending the original email, and each contact gets the first ready audience version they\'re in, otherwise their share of the test. By default it starts as a copy of the email with the same block ids: then change it with groundhogg/edit-email or groundhogg/set-email-strings (and its subject with groundhogg/update-email-template), passing the variation\'s id as email_id. It starts as a draft, which nobody is sent; make it "ready" with groundhogg/update-email-template once it\'s done. See groundhogg/get-authoring-guide topic "email", section "Variations".', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'email_id' ],
				'properties'           => [
					'email_id' => [
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => __( 'The email to add a variation to, the one flows and broadcasts send. Not a template, a global block or another variation.', 'groundhogg' ),
					],
					'kind'     => [
						'type'        => 'string',
						'enum'        => [ 'audience', 'test' ],
						'default'     => 'audience',
						'description' => __( '"audience": for the contacts in `audience`, which is required. "test": an A/B test version, which gets `weight` percent of the contacts no audience version is for; the email keeps the rest.', 'groundhogg' ),
					],
					'audience' => self::audience_input_schema(),
					'weight'   => [
						'type'        => 'integer',
						'minimum'     => 1,
						'maximum'     => 99,
						'description' => __( 'For a test version: its share of the test, in percent. Leave it out to split the test evenly between the email and its test versions.', 'groundhogg' ),
					],
					'title'    => [
						'type'        => 'string',
						'description' => __( 'The internal title. Defaults to the email\'s title with the language.', 'groundhogg' ),
					],
					'locale'   => [
						'type'        => 'string',
						'description' => __( 'The language the variation is written in, as a WordPress locale. Defaults to the audience\'s language when it names just one.', 'groundhogg' ),
					],
					'priority' => [
						'type'        => 'integer',
						'default'     => 0,
						'description' => __( 'For a contact in the audience of more than one variation: the lowest goes first, then the oldest.', 'groundhogg' ),
					],
					'copy'     => [
						'type'        => 'boolean',
						'default'     => true,
						'description' => __( 'Start from a copy of the email: its subject, content and settings. false starts with no content, for writing it with groundhogg/update-email-template.', 'groundhogg' ),
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'variation' => Email_Schema::get_schema(),
				],
			],
		];
	}

	public function __invoke( $input ) {

		$email = new Email( absint( $input['email_id'] ) );

		if ( ! $email->exists() ) {
			return new WP_Error( 'groundhogg_email_not_found', __( 'Email not found.', 'groundhogg' ) );
		}

		if ( ! current_user_can( 'edit_email', $email ) ) {
			return new WP_Error( 'groundhogg_cannot_edit_email', __( 'You are not allowed to edit this email.', 'groundhogg' ) );
		}

		$is_test = ( $input['kind'] ?? 'audience' ) === 'test';
		$args    = [
			'kind'     => $is_test ? 'test' : 'audience',
			'title'    => $input['title'] ?? '',
			'locale'   => $input['locale'] ?? '',
			'priority' => $input['priority'] ?? 0,
			'copy'     => $input['copy'] ?? true,
			'status'   => 'draft',
		];

		if ( $is_test ) {

			if ( ! empty( $input['audience'] ) ) {
				return new WP_Error( 'groundhogg_test_has_no_audience', __( 'A test version has no audience, it gets a share of the contacts the email is for. Give it a `weight`, or leave that out for an even split.', 'groundhogg' ) );
			}

			if ( isset( $input['weight'] ) ) {
				$args['weight'] = $input['weight'];
			}
		} else {

			$audience = self::audience_from_input( is_array( $input['audience'] ?? null ) ? $input['audience'] : [] );

			if ( is_wp_error( $audience ) ) {
				return $audience;
			}

			$args['audience'] = $audience;
		}

		$variation = Email_Variations::create( $email, $args );

		if ( is_wp_error( $variation ) ) {
			return new WP_Error( 'groundhogg_' . $variation->get_error_code(), $variation->get_error_message() );
		}

		return [
			'variation' => Email_Schema::transform( $variation, [ 'version' ] ),
		];
	}
}
