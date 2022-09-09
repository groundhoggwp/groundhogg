<?php

namespace Groundhogg\Form\Fields;

use Groundhogg\Plugin;
use function Groundhogg\html;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-05-09
 * Time: 4:25 PM
 */

/**
 * TODO Support for file types....
 *
 * Class File
 * @package Groundhogg\Form\Fields
 */
class File extends Input {

	public function get_default_args() {
		return [
			'type'          => 'file',
			'label'         => _x( 'File *', 'form_default', 'groundhogg' ),
			'name'          => '',
			'id'            => '',
			'class'         => 'gh-file-uploader',
			'max_file_size' => wp_max_upload_size(),
			'file_types'    => implode( ',', $this->get_default_file_types() ),
			'required'      => false,
			'attributes'    => '',
		];
	}

	/**
	 * Get the name of the shortcode
	 *
	 * @return string
	 */
	public function get_shortcode_name() {
		return 'file';
	}

	private function get_default_file_types() {
		return [
			'.pdf',
			'.txt',
			'.text',
			'.png',
			'.jpg',
			'.jpeg',
			'.doc',
			'.docx',
		];
	}

	public function get_file_types() {
		return $this->get_att( 'file_types', $this->get_default_file_types() );
	}

	public function get_value() {
//        if ( Plugin::$instance->submission_handler->has_errors() ){
//            return Plugin::$instance->submission_handler->get_posted_file( $this->get_name() );
//        }

		return esc_attr( $this->get_att( "value" ) );
	}

	public function render() {

		$atts = [
			'type'        => 'file',
			'name'        => $this->get_name(),
			'id'          => $this->get_id(),
			'class'       => $this->get_classes() . ' gh-input',
			'value'       => $this->get_value(),
			'placeholder' => $this->get_placeholder(),
			'title'       => $this->get_title(),
			'required'    => $this->is_required(),
			'accept'      => $this->get_file_types()
		];

		$input = html()->input( $atts );

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
