<?php

namespace Groundhogg\Admin\Emails\Blocks;

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

class Divider extends Email_Block
{
    public function get_icon()
    {
        return GROUNDHOGG_ASSETS_PATH . 'images/email-icons/divider.png' ;
    }

    public function get_name()
    {
        return 'divider';
    }

    public function get_title()
    {
        return _x( 'Divider', 'email_block', 'groundhogg' );
    }

    public function get_settings()
    {
        return array(
            array(
                'type'  => 'range',
                'label' => __( 'Width' ),
                'atts'  => array(
                    'id'    => 'divider-width',
                    'name'  => 'divider-width',
                    'max'   => 100,
                    'min'   => 10
                ),
            ),
            array(
                'type'  => 'range',
                'label' => __( 'Height' ),
                'atts'  => array(
                    'id'    => 'divider-height',
                    'name'  => 'divider-height',
                    'min'   => 1,
                    'max'   => 20
                ),
            ),
            array(
                'type'  => 'input',
                'label' => __( 'Color' ),
                'atts'  => array(
                    'name' => 'divider-color',
                    'id' => 'divider-color',
                    'value' => '#E5E5E5'
                ),
            ),
        );
    }

    public function scripts()
    {
        wp_enqueue_script( 'groundhogg-email-divider' );
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
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td class="divider">
                    <div style="margin: 5px 0 5px 0"><hr style="width:80%;"/></div>
                </td>
            </tr>
        </table>
        <?php

        return ob_get_clean();
    }

}