<?php

namespace Groundhogg\Form\Fields;

use function Groundhogg\html;
use Groundhogg\Plugin;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-05-09
 * Time: 4:25 PM
 */
class Checkbox extends Input {
	public function get_default_args() {
		return [
			'label'      => '',
			'name'       => '',
			'id'         => '',
			'class'      => '',
			'value'      => '1',
			'tag'        => 0,
			'title'      => '',
			'attributes' => '',
			'required'   => false,
			'callback'   => 'sanitize_textarea_field',
		];
	}

	/**
	 * Get the name of the shortcode
	 *
	 * @return string
	 */
	public function get_shortcode_name() {
		return 'checkbox';
	}

	public function is_checked() {
		$a = $this->should_auto_populate() && $this->get_value() === $this->get_data_from_contact( $this->get_name() );
		$b = Plugin::$instance->submission_handler->has_errors() && Plugin::$instance->submission_handler->get_posted_data( $this->get_name() );

		return $a || $b;
	}

	public function get_config() {
		return array_merge( [
			'tag_mapping' => [
				md5( $this->get_value() ) => $this->get_att( 'tag' )
			]
		], parent::get_config() );
	}

	/**
	 * @return string
	 */
	public function render() {
		$atts = [
			'label'    => $this->get_label(),
			'name'     => $this->get_name(),
			'id'       => $this->get_id(),
			'class'    => $this->get_classes() . ' gh-checkbox',
			'value'    => $this->get_value(),
			'title'    => $this->get_title(),
			'required' => $this->is_required(),
			'checked'  => $this->is_checked()
		];

		return html()->checkbox( $atts );
	}
}