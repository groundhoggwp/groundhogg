<?php

namespace Groundhogg\Form\Fields;

use function Groundhogg\html;

class Column extends Field {

	/**
	 * @return array|mixed
	 */
	public function get_default_args() {
		return [
			'size'  => false,
			'width' => '1/1',
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
	 * Get the field"s label
	 *
	 * @return string
	 */
	public function get_size() {
		return $this->get_att( "size" );
	}

	/**
	 * Get the field placeholder.
	 *
	 * @return string
	 */
	public function get_width() {
		return $this->get_att( "width", $this->get_size() );
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

		//backwards compat for columns with the size attr
		$width = $this->get_width();

		switch ( $width ) {
			default:
			case '1/1':
				$width = 'col-1-of-1';
				break;
			case '1/2':
				$width = 'col-1-of-2';
				break;
			case '1/3':
				$width = 'col-1-of-3';
				break;
			case '2/3':
				$width = 'col-2-of-3';
				break;
			case '1/4':
				$width = 'col-1-of-4';
				break;
			case '3/4':
				$width = 'col-3-of-4';
				break;
		}

		return html()->wrap( do_shortcode( $this->get_content() ), 'div', [
			'id'    => $this->get_id(),
			'class' => sprintf( 'gh-form-column %s %s', $this->get_classes(), $width ),
		] );
	}

	/**
	 * Get the name of the shortcode
	 *
	 * @return string
	 */
	public function get_shortcode_name() {
		return 'col';
	}
}
