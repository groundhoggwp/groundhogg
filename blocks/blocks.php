<?php

namespace Groundhogg\Blocks;

use Groundhogg\Blocks\Elementor\Form_Widget;
use function Groundhogg\get_form_list;

class Blocks {

	public function __construct() {
		$this->init_gutenberg();

		add_action( 'elementor/widgets/widgets_registered', array( $this, 'init_elementor_blocks' ) );
		add_action( 'init', array( $this, 'init_beaver_builder_blocks' ) );

	}

	public function init_gutenberg() {
//        include __DIR__ . '/gutenberg/gutenberg.php';
		include __DIR__ . '/gutenberg-new/src/init.php';
	}

	public function init_elementor_blocks() {
		try {
			\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new Form_Widget() );
		} catch ( \Exception $e ) {
		}
	}

	public function init_beaver_builder_blocks() {
		if ( class_exists( '\FLBuilder' ) ) {

			/**
			 * Register the module and its form settings.
			 */
			\FLBuilder::register_module( \Groundhogg\Blocks\Beaver_Builder\Form_Widget::class, array(
				'select-form' => array(
					'title'    => __( 'Select a form', 'groundhogg' ),
					'sections' => array(
						'groundhogg-forms' => array(
							'title'  => __( 'Groundhogg Forms', 'groundhogg' ),
							'fields' => array(
								'groundhogg_form_id' => array(
									'type'    => 'select',
									'label'   => __( 'Select a form', 'groundhogg' ),
									'options' => get_form_list()
								),
							)
						)
					)
				)
			) );
		}
	}

}