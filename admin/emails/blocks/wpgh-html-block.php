<?php

namespace Groundhogg\Admin\Emails\Blocks;

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
if (!defined('ABSPATH')) exit;

class HTML extends Email_Block
{
    public function get_icon()
    {
        return GROUNDHOGG_ASSETS_PATH . 'images/email-icons/html-block.png';
    }

    public function get_name()
    {
        return 'html';
    }

    public function get_title()
    {
        return _x('HTML', 'email_block', 'groundhogg');
    }

    public function get_settings()
    {
        return array(
            array(
                'type' => 'textarea',
                'label' => __('Content'),
                'atts' => array(
                    'id' => 'html-content',
                    'name' => 'html-content',
                    'rows' => 30,
                    'cols' => 37,
                ),
            ),
        );
    }

    public function scripts()
    {
        wp_enqueue_script('groundhogg-email-html');
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
        <div>
            <p><?php _e('This is some custom HTML which you can edit on the right. You may enter any valid HTML tags, but they may get filtered out as some email browsers to not support certain HTML.', 'groundhogg'); ?></p>
        </div>
        <?php

        return ob_get_clean();
    }


    /**
     * Build the settings panel for the block
     */
    public function settings_panel()
    {

        $block_settings = $this->register_settings();

        $html = sprintf("<div id=\"%s-block-editor\" data-block-settings=\"%s\" class=\"postbox hidden\">", $this->name, $this->name);
        $html .= sprintf("<h3 class=\"hndle\">%s</h3>", $this->title);
        $html .= "<div class=\"inside\" style='margin:0;padding:0;'><div class=\"options\">";
        foreach ($block_settings as $i => $settings) {

            if (isset($settings['type']) && method_exists(WPGH()->html, $settings['type'])) {
                $html .= sprintf("<td>%s</td>", call_user_func(array(WPGH()->html, $settings['type']), $settings['atts']));
            }
        }

        $html .= '</table>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        echo $html;
    }


}