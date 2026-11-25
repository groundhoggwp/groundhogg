<?php

namespace Groundhogg\AI;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One thing the site can ask an AI provider to do, like writing alt text for an image. The browser asks for a job by
 * name and gives its input, see AI::run(), and the job writes the prompt, so the site's key can only be used for what a
 * job does, never as a general-purpose AI.
 *
 * Add one with the groundhogg/ai/jobs filter.
 */
abstract class Job {

	/**
	 * What a job makes, which decides what the provider has to support
	 */
	const TEXT = 'text';
	const IMAGE = 'image';

	/**
	 * The name the browser asks for it by, like alt-text
	 *
	 * @return string
	 */
	abstract public function get_name(): string;

	/**
	 * What it does, for people, like in the usage report
	 *
	 * @return string
	 */
	abstract public function get_label(): string;

	/**
	 * The capability someone needs besides use_ai, the one for what the job changes, like upload_files for an image's
	 * alt text
	 *
	 * @return string
	 */
	abstract public function get_capability(): string;

	/**
	 * Check and clean the input
	 *
	 * @param array $input
	 *
	 * @return array|WP_Error the input to work with
	 */
	abstract public function validate( array $input );

	/**
	 * The prompt, from wp_ai_client_prompt(), see new_prompt()
	 *
	 * @param array $input from validate()
	 *
	 * @return \WP_AI_Client_Prompt_Builder
	 */
	abstract public function prompt( array $input );

	/**
	 * What the browser gets back, from what the provider made
	 *
	 * @param \WordPress\AiClient\Results\DTO\GenerativeAiResult $result
	 * @param array                                               $input from validate()
	 *
	 * @return array|WP_Error
	 */
	abstract public function respond( $result, array $input );

	/**
	 * text or image
	 *
	 * @return string
	 */
	public function get_kind(): string {
		return self::TEXT;
	}

	/**
	 * Send the prompt. Checks the provider can do it first, since the client's own errors about models are written for
	 * developers.
	 *
	 * @param \WP_AI_Client_Prompt_Builder $prompt
	 *
	 * @return \WordPress\AiClient\Results\DTO\GenerativeAiResult|WP_Error
	 */
	public function generate( $prompt ) {

		$image = $this->get_kind() === self::IMAGE;

		if ( ! ( $image ? $prompt->is_supported_for_image_generation() : $prompt->is_supported_for_text_generation() ) ) {
			return new WP_Error( 'ai_not_supported', __( 'None of the AI providers on this site can do this.', 'groundhogg' ) );
		}

		return $image ? $prompt->generate_image_result() : $prompt->generate_text_result();
	}

	/**
	 * A new prompt from WordPress's AI client
	 *
	 * @param mixed $prompt
	 *
	 * @return \WP_AI_Client_Prompt_Builder
	 */
	protected function new_prompt( $prompt = null ) {
		return wp_ai_client_prompt( $prompt );
	}
}
