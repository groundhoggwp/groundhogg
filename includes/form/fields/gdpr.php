<?php

namespace Groundhogg\Form\Fields;

use function Groundhogg\get_default_field_label;
use function Groundhogg\html;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-05-09
 * Time: 4:25 PM
 */
class GDPR extends Checkbox {
	public function get_default_args() {
		return [
			'label'    => get_default_field_label( 'gdpr_consent' ),
			'label-2'  => get_default_field_label( 'marketing_consent' ),
			'name'     => 'gdpr_consent',
			'id'       => 'gdpr_consent',
			'name-2'   => 'marketing_consent',
			'id-2'     => 'marketing_consent',
			'class'    => 'gh-gdpr',
			'value'    => 'yes',
			'tag'      => 0,
			'title'    => _x( 'I Consent', 'form_default', 'groundhogg' ),
			'required' => true,
		];
	}

	/**
	 * Get the name of the shortcode
	 *
	 * @return string
	 */
	public function get_shortcode_name() {
		return 'gdpr';
	}

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

		$atts2 = [
			'label'    => $this->get_att( "label-2" ),
			'name'     => $this->get_att( "name-2" ),
			'id'       => $this->get_att( "id-2" ),
			'class'    => $this->get_classes() . ' gh-checkbox',
			'value'    => $this->get_value(),
			'title'    => $this->get_title(),
			'required' => $this->is_required(),
			'checked'  => $this->is_checked()
		];


		return html()->wrap( [
			html()->checkbox( $atts ),
			html()->checkbox( $atts2 ),
		], 'div', [ 'id' => 'gdpr-checkboxes-wrap' ] );
	}
}