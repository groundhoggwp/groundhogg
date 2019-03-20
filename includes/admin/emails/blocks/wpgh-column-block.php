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

class WPGH_Column_Block extends WPGH_Email_Block
{

    /**
     * Declare the block properties
     *
     * WPGH_Text_Block constructor.
     */
    public function __construct()
    {
        $this->icon = WPGH_ASSETS_FOLDER . 'images/email-icons/spacer-block.png' ;
        $this->name = 'column';
        $this->title = __( 'Column', 'groundhogg' );
//        wp_enqueue_script( 'wpgh-column-block', WPGH_ASSETS_FOLDER . 'js/admin/email-blocks/column.js', array(), filemtime( WPGH_PLUGIN_DIR . 'assets/js/admin/email-blocks/column.js' ) );
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

    /**
     * Register the block settings panel
     *
     * @return array
     */
    protected function register_settings()
    {

//        $this->settings = array(
//            array(
//                'type'  => 'number',
//                'label' => __( '' ),
//                'atts'  => array(
//                    'class' => 'input',
//                    'id'    => 'spacer-size',
//                    'name'  => 'spacer-size',
//                ),
//            ),
//        );

        return parent::register_settings();

    }


}