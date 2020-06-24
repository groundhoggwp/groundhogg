<?php

namespace Groundhogg\Form\Fields;

use Groundhogg\Plugin;
use function Groundhogg\get_array_var;

class Recaptcha extends Input {

	/**
	 * @return array|mixed
	 */
	public function get_default_args() {
		return [
			'theme'         => false,
			'captcha-theme' => 'light',
			'size'          => false,
			'captcha-size'  => 'normal',
		];
	}

	/**
	 * Get the recaptcha version, by default it's a checkbox.
	 *
	 * @return string
	 */
	public static function get_version() {
		return get_option( 'gh_recaptcha_version', 'v2' ) ?: 'v2';
	}

	/**
	 * @return string
	 */
	public function get_theme() {
		return $this->get_att( 'theme', $this->get_att( 'captcha-theme' ) );
	}

	/**
	 * @return string
	 */
	public function get_size() {
		return $this->get_att( 'size', $this->get_att( 'captcha-size' ) );
	}

	public function get_name() {
		return 'g-recaptcha-response';
	}

	public function get_id() {
		return 'g-recaptcha-response';
	}

	/**
	 * Render the field in HTML
	 *
	 * @return string
	 */
	public function render() {

		$version = self::get_version();

		if ( $version === 'v2' ) {

			wp_enqueue_script( 'google-recaptcha' );

			return sprintf( '<div class="g-recaptcha" data-sitekey="%s" data-theme="%s" data-size="%s"></div>',
				get_option( 'gh_recaptcha_site_key', '' ),
				$this->get_theme(),
				$this->get_size()
			);
		} else {
			wp_enqueue_script( 'groundhogg-google-recaptcha' );
			return "<div class='gh-recaptcha-v3' style='display: none;'>";
		}
	}

	/**
	 * Get the name of the shortcode
	 *
	 * @return string
	 */
	public function get_shortcode_name() {
		return 'recaptcha';
	}

	/**
	 * @param $input
	 * @param $config
	 *
	 * @return \WP_Error|true
	 */
	public static function validate( $input, $config ) {
		$file_name = sprintf(
			"https://www.google.com/recaptcha/api/siteverify?secret=%s&response=%s",
			Plugin::$instance->settings->get_option( 'gh_recaptcha_secret_key' ),
			$input
		);

		$verifyResponse = wp_remote_get( $file_name );
		$responseData   = json_decode( wp_remote_retrieve_body( $verifyResponse ) );

		$bot_error = new \WP_Error( 'captcha_verification_failed', _x( 'Failed reCAPTCHA verification. You are probably a robot.', 'submission_error', 'groundhogg' ) );

		if ( $responseData->success == false ) {
			return $bot_error;
		}

		// Check the score...
		if ( self::get_version() === 'v3' ){
			$score = get_array_var( $responseData, 'score' );

			if ( ! $score ){
				return $bot_error;
			}

			$score_threshold = floatval( apply_filters( 'groundhogg/recaptcha/v3/score_threshold', get_option( 'gh_recaptcha_v3_score_threshold', 0.5 ) ) );
			$score_threshold = $score_threshold?:0.5;

			if ( $score < $score_threshold ){
				return $bot_error;
			}
		}


		return true;
	}
}