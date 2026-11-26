<?php

namespace Groundhogg\AI\Jobs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Preview text ideas for an email: the line inboxes show after the subject. See Email_Ideas.
 */
class Preview_Text extends Email_Ideas {

	public function get_name(): string {
		return 'preview-text';
	}

	public function get_label(): string {
		return __( 'Preview text ideas', 'groundhogg' );
	}

	public function get_description(): string {
		return __( 'The ideas button next to an email\'s preview text in the email editor.', 'groundhogg' );
	}

	protected function instruction(): string {
		return sprintf( 'You write preview text for marketing emails: the line an inbox shows right after the subject line. Write %d different preview texts for the email you are given. Each is between 40 and 90 characters, works together with the subject line without repeating it, and gives one more reason to open, like the key detail, the benefit, or what is inside. If there is no subject line yet, write them so they still make sense on their own. Vary them, and don\'t start with "In this email".', self::COUNT );
	}

	protected function nothing_message(): string {
		return __( 'The AI provider didn\'t come up with any preview text. Try again.', 'groundhogg' );
	}
}
