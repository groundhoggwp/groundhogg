<?php

namespace Groundhogg\AI;

use function Groundhogg\get_post_var;
use function Groundhogg\verify_admin_ajax_nonce;

class AI_Handler {

	public AI_Generator $generator;

	public function __construct() {
		add_action( 'wp_ajax_gh_ai_subject_line', [ $this, 'ajax_ai_subject_line' ] );
		add_action( 'wp_ajax_gh_ai_simple_text', [ $this, 'ajax_ai_simple_text' ] );
		add_action( 'wp_ajax_gh_ai_formatted_text', [ $this, 'ajax_ai_formatted_text' ] );
		add_action( 'wp_ajax_gh_ai_image', [ $this, 'ajax_ai_image' ] );
	}

	public function set_ai( AI_Generator $generator ) {
		$this->generator = $generator;
	}

	public function __call( $name, $arguments ) {
		if ( method_exists( $this->generator, $name ) ) {
			return call_user_func_array( [ $this->generator, $name ], $arguments );
		}

		throw new \BadMethodCallException( 'Invalid method call.' );
	}


	public function ajax_ai_subject_line() {

	}


	/**
	 * Generate simple text using the generator
	 *
	 * @return void
	 */
	public function ajax_ai_simple_text() {

		if ( ! verify_admin_ajax_nonce() ){
			wp_send_json_error();
		}

		$prompt = sanitize_textarea_field( get_post_var( 'prompt' ) );
		$text   = sanitize_textarea_field( get_post_var( 'text' ) );

		if ( $text ) {
			$text = $this->tweak_simple_text( $text, $prompt );
		} else {
			$text = $this->simple_text( $prompt );
		}

		wp_send_json_success( sanitize_textarea_field( $text ) );
	}

}
