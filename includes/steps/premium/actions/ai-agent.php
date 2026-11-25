<?php

namespace Groundhogg\Steps\Premium\Actions;

use Groundhogg\Steps\Actions\Action;
use Groundhogg\Steps\Premium\Trait_Premium_Step;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The AI Agent step without Advanced Features, so it's there to find in the flow editor. The step itself is in
 * groundhogg-pro, GroundhoggPro\Steps\Actions\AI_Agent, which replaces this one when it's active.
 */
class AI_Agent extends Action {

	use Trait_Premium_Step;

	/**
	 * Get the element name
	 *
	 * @return string
	 */
	public function get_name() {
		return esc_html_x( 'AI Agent', 'step_name', 'groundhogg' );
	}

	/**
	 * Get the element type
	 *
	 * @return string
	 */
	public function get_type() {
		return 'ai_agent';
	}

	/**
	 * Add to CRM sub group
	 *
	 * @return string
	 */
	public function get_sub_group() {
		return 'crm';
	}

	/**
	 * Get the description
	 *
	 * @return string
	 */
	public function get_description() {
		return _x( 'Ask an AI model about the contact and save its answers to their fields, tags and notes.', 'step_description', 'groundhogg' );
	}

	/**
	 * Get the icon URL
	 *
	 * @return string
	 */
	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/crm/ai-agent.svg';
	}
}
