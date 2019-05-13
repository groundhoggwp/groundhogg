<?php
namespace Groundhogg\Admin\Emails\Blocks;

/**
 * Text block
 *
 * The text block used in the email builder
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

class Text extends Block
{
    public function get_icon()
    {
        return GROUNDHOGG_ASSETS_URL . 'images/email-icons/text-block.png' ;
    }

    public function get_name()
    {
        return 'text';
    }

    public function get_title()
    {
        return _x('Text Block', 'email_block', 'groundhogg');
    }

    public function get_settings()
    {
        return  array(
            array(
                'type'  => 'number',
                'label' => __( 'H1 Size' ),
                'atts'  => array(
                    'class' => 'input',
                    'id'    => 'h1-size',
                    'name'  => 'h1-size',
                    'min'   => 10,
                    'max'   => 60,
                    'value' => 30,
                ),
            ),
            array(
                'type'  => 'font_picker',
                'label' => __( 'H1 Font' ),
                'atts'  => array(
                    'name'      => 'h1-font',
                    'id'        => 'h1-font',
                ),
            ),
            array(
                'type'  => 'number',
                'label' => __( 'H2 Size' ),
                'atts'  => array(
                    'class' => 'input',
                    'id'    => 'h2-size',
                    'name'  => 'h2-size',
                    'min'   => 10,
                    'max'   => 60,
                    'value' => 20,
                ),
            ),
            array(
                'type'  => 'font_picker',
                'label' => __( 'H2 Font' ),
                'atts'  => array(
                    'name'      => 'h2-font',
                    'id'        => 'h2-font',
                ),
            ),
            array(
                'type'  => 'number',
                'label' => __( 'Paragraph Size' ),
                'atts'  => array(
                    'class' => 'input',
                    'id'    => 'p-size',
                    'name'  => 'p-size',
                    'min'   => 10,
                    'max'   => 60,
                    'value' => 16,
                ),
            ),
            array(
                'type'  => 'font_picker',
                'label' => __( 'Paragraph Font' ),
                'atts'  => array(
                    'name'      => 'p-font',
                    'id'        => 'p-font',
                ),
            ),
        );
    }

    public function scripts()
    {
        wp_enqueue_editor();
        wp_enqueue_style('editor-buttons');
        wp_enqueue_style('groundhogg-admin-simple-editor' );

        wp_enqueue_script('wplink');
        wp_enqueue_script( 'groundhogg-admin-simple-editor' );
        wp_enqueue_script( 'groundhogg-email-text' );
    }


    /**
     * Return the inner html of the block
     *
     * @return string
     */
    protected function inner_html()
    {
        return sprintf(
            '<p>%s</p>',
            __( 'Customize this section by editing the text, adding your own copy, 
            using the options above to bold, italicize, or create links and bullets, 
            or use the options in the "Design" panel on the left to change 
            the font styles of your email.', 'groundhogg' )
        );
    }

}