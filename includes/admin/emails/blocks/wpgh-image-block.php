<?php
/**
 * Image block
 *
 * The image block used in the email builder
 *
 * @package     Admin
 * @subpackage  Admin/Emails/Blocks
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.9
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class WPGH_Image_Block extends WPGH_Email_Block
{

    /**
     * Declare the block properties
     *
     * WPGH_Text_Block constructor.
     */
    public function __construct()
    {

        $this->icon = WPGH_ASSETS_FOLDER . 'images/email-icons/image-block.png' ;
        $this->name = 'image';
        $this->title = __( 'Image', 'groundhogg' );

        wp_enqueue_script( 'wp-color-picker' );
        wp_enqueue_script( 'wpgh-image-block', WPGH_ASSETS_FOLDER . 'js/admin/email-blocks/image.min.js', array(), filemtime( WPGH_PLUGIN_DIR . 'assets/js/admin/email-blocks/image.min.js' ) );

        parent::__construct();

    }

    /**
     * Return the inner html of the block
     *
     * @return string
     */
    protected function inner_html()
    {
        ob_start();

        $src = 'https://via.placeholder.com/350x150';
        ?>
        <div class="image-wrapper" style="text-align: center"><a href=""><img width="350px" src="<?php echo $src;?>" style="max-width: 100%;width: 350px" title="" alt=""></a></div>
        <?php

        return ob_get_clean();
    }

    /**
     * Register the block settings panel
     *
     * @return array
     */
    protected function register_settings()
    {

        $this->settings = array(
            array(
                'type'  => 'image_picker',
                'label' => __( 'Image' ),
                'atts'  => array(
                    'class' => 'input',
                    'id'    => 'image',
                    'name'  => 'image',
                ),
            ),
            array(
                'type'  => 'number',
                'label' => __( 'Width' ),
                'atts'  => array(
                    'class' => 'input',
                    'id'    => 'image-width',
                    'name'  => 'image-width',
                ),
            ),
            array(
                'type'  => 'dropdown',
                'label' => __( 'Alignment' ),
                'atts'  => array(
                    'id'      => 'image-align',
                    'name'    => 'image-align',
                    'options' => array(
                        'left'      => __( 'Left' ),
                        'center'    => __( 'Center' ),
                        'right'     => __( 'Right' ),
                    ),
                ),
            ),
            array(
                'type'  => 'input',
                'label' => __( 'Link' ),
                'atts'  => array(
                    'class' => 'input',
                    'id'    => 'image-link',
                    'name'  => 'image-link',
                ),
            ),
        );

        return parent::register_settings();

    }


}