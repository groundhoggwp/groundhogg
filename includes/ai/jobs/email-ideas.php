<?php

namespace Groundhogg\AI\Jobs;

use Groundhogg\AI\Job;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A few short ideas for one line of an email, like its subject or its preview text, from what the email says. The
 * email editor sends the email's text as it is right now, saved or not, with its subject and preview text, and gets
 * the ideas back to pick from.
 *
 * A job for another line says what to write, see instruction(), the rest is the same.
 */
abstract class Email_Ideas extends Job {

	/**
	 * How many ideas to ask for
	 */
	const COUNT = 5;

	/**
	 * How much of the email is sent. The start of an email says what it's about, and this keeps the cost down.
	 */
	const MAX_CONTENT = 6000;

	/**
	 * What to write, for the system instruction. Asked for in the email's language, as JSON.
	 *
	 * @return string
	 */
	abstract protected function instruction(): string;

	/**
	 * What the provider came up with when it came up with nothing
	 *
	 * @return string
	 */
	abstract protected function nothing_message(): string;

	public function get_capability(): string {
		return 'edit_emails';
	}

	/**
	 * Takes content, the email's text, and the subject and preview it has now, if any
	 *
	 * @param array $input
	 *
	 * @return array|WP_Error [ content, subject, preview ]
	 */
	public function validate( array $input ) {

		$clean = function ( $text, int $max ) {
			// newlines are kept, they tell the model where paragraphs are, and where HTML ends one so words don't run together
			$text = preg_replace( '#<br\s*/?>|</(p|h[1-6]|li|div|td|tr|blockquote)\s*>#i', "\n", (string) $text );
			$text = wp_strip_all_tags( $text );
			$text = preg_replace( "/[ \t]+/", ' ', $text );
			$text = preg_replace( "/\n\s*\n+/", "\n\n", $text );

			return trim( mb_substr( trim( $text ), 0, $max ) );
		};

		$content = $clean( $input['content'] ?? '', self::MAX_CONTENT );

		// a few words aren't enough to say what the email is about. Counted in characters, since not every language
		// puts spaces between words
		if ( mb_strlen( $content ) < 20 ) {
			return new WP_Error( 'ai_no_content', __( 'Write some of the email first, so there\'s something to go on.', 'groundhogg' ) );
		}

		return [
			'content' => $content,
			'subject' => $clean( $input['subject'] ?? '', 200 ),
			'preview' => $clean( $input['preview'] ?? '', 300 ),
		];
	}

	public function prompt( array $input ) {

		$ask = '';

		if ( $input['subject'] ) {
			/* translators: %s: the email's subject line */
			$ask .= sprintf( __( 'The subject line now: %s', 'groundhogg' ), $input['subject'] ) . "\n";
		}

		if ( $input['preview'] ) {
			/* translators: %s: the email's preview text */
			$ask .= sprintf( __( 'The preview text now: %s', 'groundhogg' ), $input['preview'] ) . "\n";
		}

		$ask .= "\n" . __( 'The email:', 'groundhogg' ) . "\n\n" . $input['content'];

		return $this->new_prompt()
		            ->using_system_instruction( $this->instruction() . ' Write them in the language the email is written in. No clickbait, no ALL CAPS, no spam words like "free!!!" or "act now", and at most one emoji across all of them. Keep a merge tag in curly braces, like {first}, only if the email\'s current subject line or preview text uses it, and never invent new ones. Reply with JSON only.' )
		            ->with_text( $ask )
		            ->as_json_response( [
			            'type'                 => 'object',
			            'properties'           => [
				            'ideas' => [
					            'type'  => 'array',
					            'items' => [ 'type' => 'string' ],
				            ],
			            ],
			            'required'             => [ 'ideas' ],
			            'additionalProperties' => false,
		            ] )
		            // no temperature: models that reason before they answer refuse one
		            ->using_max_tokens( 600 );
	}

	/**
	 * @param \WordPress\AiClient\Results\DTO\GenerativeAiResult $result
	 * @param array                                               $input
	 *
	 * @return array|WP_Error [ ideas ]
	 */
	public function respond( $result, array $input ) {

		$text = trim( $result->toText() );
		$json = json_decode( $text, true );

		if ( is_array( $json ) && isset( $json['ideas'] ) && is_array( $json['ideas'] ) ) {
			$ideas = $json['ideas'];
		} else {
			// a provider that answered with a list instead
			$ideas = preg_split( "/\r?\n/", $text );
		}

		$clean = [];

		foreach ( $ideas as $idea ) {

			if ( ! is_string( $idea ) ) {
				continue;
			}

			$idea = trim( wp_strip_all_tags( $idea ) );
			// what models add despite being asked not to: numbering, bullets and quotes
			$idea = preg_replace( '/^(\d+[.)]|[-*•])\s*/u', '', $idea );
			$idea = trim( $idea, " \t\"'“”‘’" );
			$idea = mb_substr( $idea, 0, 200 );

			if ( $idea !== '' && ! in_array( mb_strtolower( $idea ), array_map( 'mb_strtolower', $clean ), true ) ) {
				$clean[] = $idea;
			}
		}

		if ( empty( $clean ) ) {
			return new WP_Error( 'ai_empty', $this->nothing_message() );
		}

		return [
			'ideas' => array_slice( $clean, 0, self::COUNT ),
		];
	}
}
