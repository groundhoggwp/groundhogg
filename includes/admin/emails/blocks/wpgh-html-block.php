<?php
/**
 * HTML block
 *
 * The HTML block used in the email builder
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

class WPGH_HTML_Block extends WPGH_Email_Block
{

    /**
     * Declare the block properties
     *
     * WPGH_Text_Block constructor.
     */
    public function __construct()
    {

        $this->icon = WPGH_ASSETS_FOLDER . 'images/email-icons/html-block.png' ;
        $this->name = 'html';
        $this->title = __( 'HTML', 'groundhogg' );

        wp_enqueue_script( 'wpgh-html-block', WPGH_ASSETS_FOLDER . 'js/admin/email-blocks/html.js', array(), filemtime( WPGH_PLUGIN_DIR . 'assets/js/admin/email-blocks/html.js' ) );

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

        ?>
        <div><p><?php _e('This is some custom HTML which you can edit on the right. You may enter any valid HTML tags, but they may get filtered out as some email browsers to not support certain HTML.', 'groundhogg'); ?></p></div>
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
                'type'  => 'textarea',
                'label' => __( 'Content' ),
                'atts'  => array(
                    'id'    => 'html-content',
                    'name'  => 'html-content',
                    'rows'  => 20,
                    'cols'  => 25,
                ),
            ),
        );

        return parent::register_settings();

    }


}