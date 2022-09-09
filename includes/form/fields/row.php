<?php

namespace Groundhogg\Form\Fields;

use function Groundhogg\html;

class Row extends Field {

	/**
	 * @return array|mixed
	 */
	public function get_default_args() {
		return [
			'id'    => '',
			'class' => ''
		];
	}

	/**
	 * Get the field ID
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->get_att( "id" );
	}

	/**
	 * @return string
	 */
	public function get_classes() {
		return esc_attr( $this->get_att( "class" ) );
	}

	public function get_content() {
		return $this->content;
	}

	/**
	 * Render the field in HTML
	 *
	 * @return string
	 */
	public function render() {
		return html()->wrap( do_shortcode( $this->get_content() ), 'div', [
			'id'    => $this->get_id(),
			'class' => sprintf( 'gh-form-row %s', $this->get_classes() )
		] );
	}

	/**
	 * Get the name of the shortcode
	 *
	 * @return string
	 */
	public function get_shortcode_name() {
		return 'row';
	}
}
