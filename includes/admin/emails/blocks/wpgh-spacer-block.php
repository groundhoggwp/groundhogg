<?php
/**
 * Spacer block
 *
 * The spacer block used in the email builder
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

class WPGH_Spacer_Block extends WPGH_Email_Block
{

    /**
     * Declare the block properties
     *
     * WPGH_Text_Block constructor.
     */
    public function __construct()
    {

        $this->icon = WPGH_ASSETS_FOLDER . 'images/email-icons/spacer-block.png' ;
        $this->name = 'spacer';
        $this->title = __( 'Spacer', 'groundhogg' );

        wp_enqueue_script( 'wpgh-spacer-block', WPGH_ASSETS_FOLDER . 'js/admin/email-blocks/spacer.min.js', array(), filemtime( WPGH_PLUGIN_DIR . 'assets/js/admin/email-blocks/spacer.min.js' ) );


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
        <table width="100%">
            <tr>
                <td class="spacer" height="10"></td>
            </tr>
        </table>
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
                'type'  => 'number',
                'label' => __( 'Spacer Height' ),
                'atts'  => array(
                    'class' => 'input',
                    'id'    => 'spacer-size',
                    'name'  => 'spacer-size',
                ),
            ),
        );

        return parent::register_settings();

    }


}