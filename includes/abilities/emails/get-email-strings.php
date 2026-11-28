<?php

namespace Groundhogg\Abilities\Emails;

use Groundhogg\Abilities\Ability;
use Groundhogg\Email;
use Groundhogg\Email_Strings;
use Groundhogg\Email_Variations;
use WP_Error;
use function Groundhogg\get_edit_version;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * An email's text as a list of strings, for translating it. On a variation, each string next to the email's and
 * whether it's translated. See Email_Strings.
 */
class Get_Email_Strings extends Ability {

	protected const NAME       = 'groundhogg/get-email-strings';
	protected const CATEGORY   = 'groundhogg-email';
	protected const CAPABILITY = 'view_emails';

	protected const READONLY   = true;
	protected const IDEMPOTENT = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'Get Email Strings', 'groundhogg' ),
			'description' => __( 'Read an email\'s text as a list of strings to translate: the subject, preview text, each paragraph, heading, list item, button and menu label, the text in HTML blocks, and image and video alt text, by key. `not_translated` lists the blocks whose text isn\'t in the strings, like global blocks and posts. Inline tags are placeholders like <1>bold</1> or <2/>, so there are no styles or links to keep. For a variation (see groundhogg/create-email-variation), each string comes with the original email\'s `source` and a `status`, and `missing` lists text the email has that the variation doesn\'t. Translate them with groundhogg/set-email-strings. Only for emails built with the block editor or the HTML editor.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'email_id' ],
				'properties'           => [
					'email_id'          => [
						'type'    => 'integer',
						'minimum' => 1,
					],
					'needs_translation' => [
						'type'        => 'boolean',
						'default'     => false,
						'description' => __( 'For a variation: only the strings that are untranslated or whose source changed.', 'groundhogg' ),
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'email_id'     => [ 'type' => 'integer' ],
					'variation_of' => [ 'type' => 'integer' ],
					'locale'       => [
						'type'        => 'string',
						'description' => __( 'For a variation: the language to translate into, as a WordPress locale.', 'groundhogg' ),
					],
					'version'      => [
						'type'        => 'string',
						'description' => __( 'Give it to groundhogg/set-email-strings as expected_version.', 'groundhogg' ),
					],
					'strings'      => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'key'    => [ 'type' => 'string' ],
								'text'   => [ 'type' => 'string' ],
								'source' => [
									'type'        => 'string',
									'description' => __( 'For a variation: what the original email says, when it\'s different from text.', 'groundhogg' ),
								],
								'status' => [
									'type'        => 'string',
									'enum'        => [ 'untranslated', 'changed', 'translated', 'not_in_email' ],
									'description' => __( 'For a variation. "untranslated": it\'s still the original\'s text. "changed": the original changed since it was translated, so translate `source` again. "translated": done. "not_in_email": the original doesn\'t have it any more.', 'groundhogg' ),
								],
							],
						],
					],
					'missing'      => [
						'type'        => 'array',
						'description' => __( 'For a variation: text the original email has that the variation doesn\'t, because blocks were added to the email after the variation was made. Add those blocks to the variation with groundhogg/edit-email.', 'groundhogg' ),
						'items'       => [
							'type'       => 'object',
							'properties' => [
								'key'    => [ 'type' => 'string' ],
								'source' => [ 'type' => 'string' ],
							],
						],
					],
					'not_translated' => self::not_translated_schema(),
				],
			],
		];
	}

	/**
	 * The schema of the blocks whose text isn't strings, see Email_Strings::not_translated()
	 *
	 * @return array
	 */
	public static function not_translated_schema(): array {
		return [
			'type'        => 'array',
			'description' => __( 'Blocks whose text isn\'t in the strings, so translating the strings leaves them as they are: global blocks, and dynamic blocks like posts or a shortcode, whose content comes from the site when the email is sent. When you say a translation is done, say these are left to check by hand.', 'groundhogg' ),
			'items'       => [
				'type'       => 'object',
				'properties' => [
					'id'   => [ 'type' => 'string' ],
					'type' => [ 'type' => 'string' ],
					'note' => [
						'type'        => 'string',
						'description' => __( 'What to do about it.', 'groundhogg' ),
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

		$output = [
			'email_id'     => $email->get_id(),
			'variation_of' => $email->get_parent_id(),
			'version'      => get_edit_version( $email ),
		];

		if ( ! $email->is_variation() ) {

			$strings = Email_Strings::get( $email );

			if ( is_wp_error( $strings ) ) {
				return new WP_Error( 'groundhogg_' . $strings->get_error_code(), $strings->get_error_message() );
			}

			$output['strings'] = [];

			foreach ( $strings as $key => $text ) {
				$output['strings'][] = [ 'key' => (string) $key, 'text' => $text ];
			}

			$output['not_translated'] = Email_Strings::not_translated( $email );

			return $output;
		}

		$compared = Email_Strings::compare( $email );

		if ( is_wp_error( $compared ) ) {
			return new WP_Error( 'groundhogg_' . $compared->get_error_code(), $compared->get_error_message() );
		}

		$strings = array_map( function ( $string ) {
			unset( $string['remembered'] );

			return $string;
		}, $compared['strings'] );

		if ( ! empty( $input['needs_translation'] ) ) {
			$strings = array_filter( $strings, function ( $string ) {
				return in_array( $string['status'], [ 'untranslated', 'changed' ], true );
			} );
		}

		$output['locale']  = (string) $email->get_meta( Email_Variations::LOCALE_KEY );
		$output['strings'] = array_values( $strings );
		$output['missing'] = $compared['missing'];

		$output['not_translated'] = Email_Strings::not_translated( $email );

		return $output;
	}
}
