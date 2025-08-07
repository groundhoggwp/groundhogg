<?php

namespace Groundhogg\Form\Fields;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-05-09
 * Time: 4:25 PM
 */
class Last extends Input {

	public function get_default_args() {
		return [
			'type'        => 'text',
			'label'       => esc_html_x( 'Last Name *', 'form_default', 'groundhogg' ),
			'name'        => 'last_name',
			'id'          => 'last_name',
			'class'       => 'gh-last-name',
			'value'       => '',
			'placeholder' => 'Doe',
			'pattern'     => '',
			'title'       => esc_html_x( 'Do not include numbers or special characters.', 'form_default', 'groundhogg' ),
			'required'    => false,
		];
	}

	/**
	 * Get the name of the shortcode
	 *
	 * @return string
	 */
	public function get_shortcode_name() {
		return 'last';
	}

	/**
	 * Return the value that will be the final value.
	 *
	 * @param $input
	 * @param $config
	 *
	 * @return string
	 */
	public static function validate( $input, $config ) {
		if ( preg_match( '/[0-9_!¡?÷?¿\/\\+=@#$%ˆ&*(){}|~<>;:[\]]/u', $input ) ) {

			if ( current_user_can( 'edit_funnels' ) ) {
				return new \WP_Error( 'invalid_last_name', __( 'Names should not contain numbers or special symbols.', 'groundhogg' ) );
			}

			return new \WP_Error( 'invalid_last_name', __( 'Please provide a valid last name.', 'groundhogg' ) );
		}

		return apply_filters( 'groundhogg/form/fields/last/validate', sanitize_textarea_field( $input ) );
	}
}
