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
if (!defined('ABSPATH')) exit;

class Spacer extends Block
{
    public function get_icon()
    {
        return GROUNDHOGG_ASSETS_URL . 'images/email-icons/spacer-block.png';
    }

    public function get_name()
    {
        return 'spacer';
    }

    public function get_title()
    {
        return _x('Spacer', 'email_block', 'groundhogg');
    }

    public function get_settings()
    {
        return array(
            array(
                'type' => 'range',
                'label' => __('Height'),
                'atts' => array(
                    'id' => 'spacer-size',
                    'name' => 'spacer-size',
                    'min' => 10,
                    'step' => 10,
                    'max' => 300,
                ),
            ),
        );

    }

    public function scripts()
    {
        wp_enqueue_script('groundhogg-email-spacer');
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
                <td class="spacer" height="10">&nbsp;</td>
            </tr>
        </table>
        <?php

        return ob_get_clean();
    }
}