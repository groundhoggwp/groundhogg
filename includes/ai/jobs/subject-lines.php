<?php

namespace Groundhogg\AI\Jobs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Subject line ideas for an email, from what it says. See Email_Ideas.
 */
class Subject_Lines extends Email_Ideas {

	public function get_name(): string {
		return 'subject-lines';
	}

	public function get_label(): string {
		return __( 'Subject line ideas', 'groundhogg' );
	}

	public function get_description(): string {
		return __( 'The ideas button next to an email\'s subject line in the email editor.', 'groundhogg' );
	}

	protected function instruction(): string {
		return sprintf( 'You write subject lines for marketing emails. Write %d different subject lines for the email you are given. Each is under 60 characters and says what the email is really about, so it gets opened by the right people. Vary the approach: one that leads with the benefit, one that sparks curiosity, one question, one plain and direct, and one with gentle urgency only if the email has a real deadline.', self::COUNT );
	}

	protected function nothing_message(): string {
		return __( 'The AI provider didn\'t come up with any subject lines. Try again.', 'groundhogg' );
	}
}
