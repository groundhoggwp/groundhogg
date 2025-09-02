<?php

namespace Groundhogg\AI;

use function Groundhogg\maybe_implode_in_quotes;

class AI_Actions {

	public function build_prompt() {

	}

	/**
	 * Generates and returns an array of subject line suggestions for an email
	 *
	 * The user prompt will be a response to the following question...
	 * "What's your email about/for?"
	 *
	 * Examples of user response might be...
	 * "Email for spring sale"
	 * "Promoting a product"
	 * "Discount win-back email"
	 *
	 * @param  string  $user_prompt
	 * @param  array  $params
	 *
	 * @return void
	 */
	public function suggest_subject_lines( string $user_prompt, array $params = [] ) {

		$params = wp_parse_args( $params, [
			'count'       => 5, // the number of subject lines to generate
			'emoji'       => 2, // the number of subject lines that should include emojis (0 for none)
			'context'     => '', // any additional context to include in the query, like email content,
			'minLen'      => 10, // the minimum length of the suggestions
			'maxLen'      => 60, // the maximum length of the suggestions
			'punctuation' => [ '!', '?', '\'', '"' ] // allowed punctuation
		] );

		$prompt =
			'Generate ' . $params['count'] . ' subject lines for an email about/for "' . $user_prompt . '". ';
			'Each subject line should appear on a newline. ' .
			'They should be short ( ' . $params['minLen'] . ' to ' . $params['maxLen'] . ' characters), clear (laymen language), engaging, and curiosity building. ' .
			'Also avoid using punctuation except for ' . maybe_implode_in_quotes( $params['punctuation'] ) . '. ' .
			'Use emojis in exactly ' . $params['emoji'] . ' of the subject lines. ' .
			'DO NOT return any other text other than the subject lines themselves. ';


		if ( ! empty( $params['context'] ) ) {
			$prompt .= "\nHere is some additional context and text regarding the email content to help inform your suggestions.\n\n";
			$prompt .= $params['context'];
		}




		// we're expecting a string back...
		$response = ai()->string();

	}

	public function suggest_previews() {

	}

	public function suggest_text() {

	}

	public function suggest_html() {

	}

	public function rewrite_text() {

	}

	public function shorten_text() {

	}

	public function lengthen_text() {

	}

	public function enhance_text() {

	}

	public function correct_text() {

	}

	public function generate_image() {

	}

}
