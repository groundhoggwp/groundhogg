<?php

namespace Groundhogg\Blocks;

use Groundhogg\Blocks\Elementor\Form_Widget;
use function Groundhogg\get_form_list;
use function Groundhogg\white_labeled_name;

class Blocks {

	public function __construct() {
		$this->init_gutenberg();

		add_action( 'elementor/widgets/register', [ $this, 'init_elementor_blocks' ] );
		add_action( 'init', [ $this, 'init_beaver_builder_blocks' ] );

	}

	public function init_gutenberg() {
//        include __DIR__ . '/gutenberg/gutenberg.php';
		include __DIR__ . '/gutenberg/src/init.php';
	}

	public function init_elementor_blocks() {
		try {
			\Elementor\Plugin::instance()->widgets_manager->register( new Form_Widget() );
		} catch ( \Exception $e ) {
		}
	}

	public function init_beaver_builder_blocks() {
		if ( class_exists( '\FLBuilder' ) ) {

			/**
			 * Register the module and its form settings.
			 */
			\FLBuilder::register_module( \Groundhogg\Blocks\Beaver_Builder\Form_Widget::class, [
				'select-form' => [
					'title'    => __( 'Select a form', 'groundhogg' ),
					'sections' => [
						'groundhogg-forms' => [
							'title'  => sprintf( __( '%s Forms', 'groundhogg' ), white_labeled_name() ),
							'fields' => [
								'groundhogg_form_id' => [
									'type'    => 'select',
									'label'   => __( 'Select a form', 'groundhogg' ),
									'options' => get_form_list()
								],
							]
						]
					]
				]
			] );
		}
	}

}
