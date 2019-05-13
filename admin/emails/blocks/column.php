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

class Column extends Block
{
    public function get_icon()
    {
        return GROUNDHOGG_ASSETS_URL . 'images/email-icons/spacer-block.png' ;
    }

    public function get_name()
    {
        return 'Column';
    }

    public function get_title()
    {
        return _x('Column', 'email_block', 'groundhogg');
    }

    public function get_settings()
    {
        // No settings
    }

    public function scripts()
    {
        // No scripts
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
        <table border="0" cellpadding="5" cellspacing="0" width="100%" class="">
            <tbody>
            <tr>
                <td align="left" valign="top" width="50%">
                    <!--LEFT COLUMN CODE START-->
                    <div class="inner-content">
                        <p><?php _e( 'Column content can only be edited in HTML mode at the moment.', 'groundhogg' ); ?></p>
                    </div>
                    <!--LEFT COLUMN CODE END-->
                </td>
                <td align="left" valign="top" width="50%">
                    <!--RIGHT COLUMN CODE START-->
                    <div class="inner-content">
                        <p><?php _e( 'Column content can only be edited in HTML mode at the moment.', 'groundhogg' ); ?></p>
                    </div>
                    <!--RIGHT COLUMN CODE END-->
                </td>
            </tr>
            </tbody>
        </table>
        <?php
        return ob_get_clean();
    }

}