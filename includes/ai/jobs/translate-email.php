<?php

namespace Groundhogg\AI\Jobs;

use Groundhogg\AI\Job;
use Groundhogg\Email;
use Groundhogg\Email_Strings;
use Groundhogg\Email_Variations;
use Groundhogg\Multilingual;
use WP_Error;
use function Groundhogg\check_lock;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Translate a variation of an email into its language, from the email it's a variation of, and save it. Only the
 * strings that need it are sent: the ones still in the email's language and the ones the email changed since. See
 * Email_Strings.
 *
 * A long email is translated a part at a time, see MAX_SOURCE: the response says how many are left, so the editor can
 * ask again.
 */
class Translate_Email extends Job {

	/**
	 * How much text is sent at once, in characters
	 */
	const MAX_SOURCE = 12000;

	public function get_name(): string {
		return 'translate-email';
	}

	public function get_label(): string {
		return __( 'Email translation', 'groundhogg' );
	}

	public function get_description(): string {
		return __( 'The Translate button of an email\'s variations in the email editor.', 'groundhogg' );
	}

	public function get_capability(): string {
		return 'edit_emails';
	}

	/**
	 * Takes the variation's email_id
	 *
	 * @param array $input
	 *
	 * @return array|WP_Error [ email_id, locale, language, strings => key => text, remaining ]
	 */
	public function validate( array $input ) {

		$variation = new Email( absint( $input['email_id'] ?? 0 ) );

		if ( ! $variation->exists() || ! $variation->is_variation() ) {
			return new WP_Error( 'ai_not_a_variation', __( 'Only a variation of an email can be translated.', 'groundhogg' ) );
		}

		if ( ! current_user_can( 'edit_email', $variation ) ) {
			return new WP_Error( 'ai_forbidden', __( 'You don\'t have permission to change this email.', 'groundhogg' ), [ 'status' => 403 ] );
		}

		$locked_by = check_lock( $variation );

		if ( $locked_by ) {
			return new WP_Error( 'ai_email_locked', sprintf(
				/* translators: %s: the name of the user editing the email */
				__( '%s is editing this variation, so it can\'t be translated until they\'re done.', 'groundhogg' ),
				get_userdata( $locked_by )->display_name
			), [ 'status' => 409 ] );
		}

		$locale = Multilingual::sanitize_locale( $variation->get_meta( Email_Variations::LOCALE_KEY ) );

		if ( ! $locale ) {
			return new WP_Error( 'ai_no_language', __( 'Say which language the variation is in first.', 'groundhogg' ) );
		}

		$compared = Email_Strings::compare( $variation );

		if ( is_wp_error( $compared ) ) {
			return $compared;
		}

		$strings = [];
		$length  = 0;
		$left    = 0;

		foreach ( $compared['strings'] as $string ) {

			if ( ! in_array( $string['status'], [ 'untranslated', 'changed' ], true ) ) {
				continue;
			}

			$source = $string['source'] ?? $string['text'];

			// at least one, however long it is
			if ( ! empty( $strings ) && $length + mb_strlen( $source ) > self::MAX_SOURCE ) {
				$left ++;
				continue;
			}

			$strings[ $string['key'] ] = $source;
			$length                    += mb_strlen( $source );
		}

		if ( empty( $strings ) ) {
			return new WP_Error( 'ai_nothing_to_translate', __( 'Everything in this variation is translated already.', 'groundhogg' ) );
		}

		return [
			'email_id'  => $variation->get_id(),
			'locale'    => $locale,
			'language'  => Multilingual::get_locale_name( $locale ),
			'strings'   => $strings,
			'remaining' => $left,
			'missing'   => count( $compared['missing'] ),
		];
	}

	public function prompt( array $input ) {

		$items = [];

		foreach ( $input['strings'] as $key => $text ) {
			$items[] = [ 'key' => (string) $key, 'text' => $text ];
		}

		$length = array_sum( array_map( 'mb_strlen', $input['strings'] ) );

		return $this->new_prompt()
		            ->using_system_instruction( sprintf(
			            'You translate marketing emails into %1$s (%2$s). You are given the strings of an email as JSON, each with a key. Translate the text of each one, so it reads as if it was written in %1$s: natural, in the same tone, not word for word. Numbered tags like <1>, </1> and <2/> stand for formatting and links: keep every one of them exactly once, around the words they belong to in the translation. Keep merge tags in curly braces like {first} or {business_name}, shortcodes in square brackets, URLs, email addresses, and the names of people, brands and products as they are. Give back every key. Reply with JSON only.',
			            $input['language'],
			            $input['locale']
		            ) )
		            ->with_text( wp_json_encode( [ 'strings' => $items ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) )
		            ->as_json_response( [
			            'type'                 => 'object',
			            'properties'           => [
				            'strings' => [
					            'type'  => 'array',
					            'items' => [
						            'type'                 => 'object',
						            'properties'           => [
							            'key'  => [ 'type' => 'string' ],
							            'text' => [ 'type' => 'string' ],
						            ],
						            'required'             => [ 'key', 'text' ],
						            'additionalProperties' => false,
					            ],
				            ],
			            ],
			            'required'             => [ 'strings' ],
			            'additionalProperties' => false,
		            ] )
		            // no temperature: models that reason before they answer refuse one. Translations are about as long
		            // as what they translate, a token is a few characters, and the rest is room to think and the JSON
		            ->using_max_tokens( min( 16000, 2000 + $length ) );
	}

	/**
	 * Save the translations to the variation
	 *
	 * @param \WordPress\AiClient\Results\DTO\GenerativeAiResult $result
	 * @param array                                               $input
	 *
	 * @return array|WP_Error [ translated, skipped, left_to_translate ]
	 */
	public function respond( $result, array $input ) {

		$json = json_decode( trim( $result->toText() ), true );

		if ( ! is_array( $json ) || ! isset( $json['strings'] ) || ! is_array( $json['strings'] ) ) {
			return new WP_Error( 'ai_empty', __( 'The AI provider didn\'t give back a translation. Try again.', 'groundhogg' ) );
		}

		$translations = [];

		foreach ( $json['strings'] as $item ) {
			if ( is_array( $item ) && isset( $item['key'], $item['text'] ) && is_string( $item['text'] ) && array_key_exists( (string) $item['key'], $input['strings'] ) ) {
				$translations[ (string) $item['key'] ] = $item['text'];
			}
		}

		if ( empty( $translations ) ) {
			return new WP_Error( 'ai_empty', __( 'The AI provider didn\'t give back a translation. Try again.', 'groundhogg' ) );
		}

		$variation = new Email( $input['email_id'] );

		// one that lost a placeholder is left for another try, the rest are saved
		$saved = Email_Strings::set( $variation, $translations, true );

		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		if ( ! empty( $saved['changed'] ) ) {
			do_action( 'groundhogg/api/email/updated', $variation );
		}

		return [
			'translated'        => count( $translations ) - count( $saved['skipped'] ),
			'skipped'           => array_keys( $saved['skipped'] ),
			'left_to_translate' => (int) Email_Strings::count_to_translate( new Email( $variation->get_id() ) ),
		];
	}
}
