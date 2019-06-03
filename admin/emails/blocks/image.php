<?php
namespace Groundhogg\Admin\Emails\Blocks;

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

class Image extends Block
{
    public function get_icon()
    {
        return GROUNDHOGG_ASSETS_URL . 'images/email-icons/image-block.png' ;
    }

    public function get_name()
    {
        return 'image';
    }

    public function get_title()
    {
        return _x('Image', 'email_block', 'groundhogg');
    }

    public function get_settings()
    {
        return array(
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
                'type'  => 'range',
                'label' => __( 'Width' ),
                'atts'  => array(
                    'class' => 'slider',
                    'id'    => 'image-width',
                    'name'  => 'image-width',
                    'max'   => 100,
                    'min'   => 1
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
                'type'  => 'link_picker',
                'label' => __( 'Link' ),
                'atts'  => array(
                    'id'    => 'image-link',
                    'name'  => 'image-link',
                ),
            ),
        );
    }

    public function scripts()
    {
        wp_enqueue_script( 'wp-color-picker' );
        wp_enqueue_script( 'groundhogg-email-image' );
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
        <div class="image-wrapper" style="text-align: center"><a href="<?php echo esc_url( site_url() ); ?>"><img width="350px" src="<?php echo $src;?>" style="display:block;max-width: 100%;width: 350px;vertical-align: bottom;" title="" alt=""></a></div>
        <?php

        return ob_get_clean();
    }

}