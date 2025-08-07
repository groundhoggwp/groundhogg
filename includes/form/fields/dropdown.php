<?php

namespace Groundhogg\Form\Fields;

use function Groundhogg\html;
use function Groundhogg\isset_not_empty;
use Groundhogg\Plugin;
use function Groundhogg\words_to_key;

class Dropdown extends Input {

	protected $tag_map = [];

	public function get_default_args() {
		return [
			'label'      => esc_html_x( 'Select *', 'form_default', 'groundhogg' ),
			'name'       => '',
			'id'         => '',
			'class'      => '',
			'options'    => '',
			'attributes' => '',
			'title'      => '',
			'default'    => esc_html_x( 'Please select one', 'form_default', 'groundhogg' ),
			'multiple'   => false,
			'required'   => false,
		];
	}

	/**
	 * Get the name of the shortcode
	 *
	 * @return string
	 */
	public function get_shortcode_name() {
		return 'dropdown';
	}

	public function get_config() {
		return array_merge( [ 'tag_mapping' => $this->get_tag_mapping() ], parent::get_config() );
	}

	/**
	 * @param string $option
	 * @param int    $tag_id
	 */
	protected function add_tag_mapping( $option = '', $tag_id = 0 ) {
		$this->tag_map[ md5( $option ) ] = absint( $tag_id );
	}

	/**
	 * @param $option
	 *
	 * @return bool|mixed
	 */
	public function get_tag_mapping( $option = '' ) {
		// Init the tag map...
		if ( empty( $this->tag_map ) ) {
			$this->get_options();
		}

		if ( ! $option ) {
			return $this->tag_map;
		}

		if ( isset_not_empty( $this->tag_map, md5( $option ) ) ) {
			return $this->tag_map[ md5( $option ) ];
		}

		return false;
	}

	/**
	 * Get the select options
	 *
	 * @return array
	 */
	public function get_options() {
		$options = $this->get_att( 'options', [] );

		if ( is_string( $options ) ) {
			$options = explode( ',', $options );
		}

		$return = [];

		foreach ( $options as $i => $option ) {

			$value = is_string( $i ) ? $i : $option;

			/**
			 * Check if tag should be applied
			 *
			 * @since 1.1
			 */
			if ( strpos( $value, '|' ) ) {
				$parts = explode( '|', $value );
				$value = $parts[0];
				$tag   = intval( $parts[1] );

				$this->add_tag_mapping( $value, $tag );
			}

			if ( strpos( $option, '|' ) ) {
				$parts  = explode( '|', $value );
				$option = $parts[0];
			}

			$return[] = $option;
		}

		return $return;
	}

	public function is_multiple() {
		return filter_var( $this->get_att( 'multiple' ), FILTER_VALIDATE_BOOLEAN );
	}

	public function get_default() {
		return $this->get_att( 'default' );
	}

	/**
	 * Return the value that will be the final value.
	 *
	 * @param $input
	 * @param $config
	 *
	 * @return string|\WP_Error
	 */
	public static function validate( $input, $config ) {
		$options = $config['atts']['options'];

		$input = is_array( $input ) ? $input : [ $input ];

		foreach ( $input as $item ) {

			if ( ! $item ) {
				continue;
			}

			// Match the input to the options string.
			if ( strpos( $options, $item ) === false ) {
				return new \WP_Error( 'invalid_input', __( 'Please select a valid dropdown option.', 'groundhogg' ) );
			}
		}

		return implode( ', ', $input );
	}

	public function render() {

		$name = $this->get_name();

		if ( $this->is_multiple() ){
			$name .= '[]';
		}

		$atts = [
			'name'        => $name,
			'id'          => $this->get_id(),
			'class'       => $this->get_classes() . ' gh-input',
			'placeholder' => $this->get_placeholder(),
			'title'       => $this->get_title(),
			'required'    => $this->is_required(),
			'multiple'    => $this->is_multiple(),
			'options'     => array_combine( $this->get_options(), $this->get_options() ),
			'option_none' => $this->get_default(),
			'selected'    => $this->get_value(),
		];

		$input = html()->dropdown( $atts );

		// No label, do not wrap in label element.
		if ( ! $this->has_label() ) {
			return $input;
		}

		$label = html()->e( 'label', [
			'class' => 'gh-input-label',
			'for'   => $this->get_id(),
		], $this->get_label() );

		return html()->e( 'div', [ 'class' => 'form-field-with-label' ], [
			$label,
			$input
		] );
	}
}
