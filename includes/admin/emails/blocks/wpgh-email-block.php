<?php
/**
 * Email block
 *
 * Basic Email Block Template
 * Not many people no JS yet, so we're going to go with a standard PHP email block API system.
 *
 * Extend this class to create your own blocks!
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

class WPGH_Email_Block
{

    /**
     * Link to an image file for the block
     *
     * @var $icon string
     */
    public $icon = '';

    /**
     * The block name
     *
     * @var
     */
    public $name = '';

    /**
     * The blocks front end faciong name
     *
     * @var
     */
    public $title = '';

    /**
     * @var array()
     */
    public $settings = array();

    /**
     * This should be used to enqueue any js files that would act as settings for the
     * block...
     *
     * WPGH_Email_Block constructor.
     */
    public function __construct()
    {

        add_filter( 'wpgh_email_blocks', array( $this, 'register' ) );
        add_action( 'wpgh_' . $this->name . '_block_settings', array( $this, 'settings_panel' ) );
        add_action( 'wpgh_' . $this->name . '_block_html'    , array( $this, 'block_html' ) );

    }

    /**
     * This is a function which registers the email blocks
     * when being called.
     *
     * @param $blocks
     * @return array
     */
    public function register( $blocks )
    {

        if ( is_array( $blocks ) ){

            $blocks[ $this->name ][ 'icon' ] = $this->icon;
            $blocks[ $this->name ][ 'title' ] = $this->title;
            $blocks[ $this->name ][ 'name' ] = $this->name;

        }

        return $blocks;

    }

    /**
     * Gets the full block html
     */
    public function block_html()
    {

        $html = sprintf( "<div  class=\"row\" data-block='%s'>", $this->name );
        $html.= sprintf( "<div  class=\"content-wrapper %s_block\">" , $this->name );
        $html.= "<div class=\"content-inside inner-content text-content\" style=\"padding: 5px;\">";

        $html .= $this->inner_html();

        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        echo $html;

    }

    /**
     * Returns the inner html default content
     */
    protected function inner_html()
    {
        return '';
    }

    /**
     * Register the settings for this particular block.
     * It uses an API to the HTML helper class...
     *
     * @see WPGH_HTML
     * @return array
     */
    protected function register_settings(){

        /* Example */

//        $settings = array(
//            array(
//                'type'    => 'callback',
//                'label'   => 'Font Size',
//                'atts'    => array(
    //                'label'   => 'Font Size'
    //                'type'    => 'number',
    //                'id'      => 'font-size',
    //                'name'    => 'font-size',
    //                'value'   => '14',
//                )
//            )
//        );

        $this->settings = apply_filters( $this->name . '_block_settings', $this->settings );

        return $this->settings;

    }

    /**
     * Build the settings panel for the block
     */
    public function settings_panel()
    {

        $block_settings = $this->register_settings();

        $html = sprintf( "<div id=\"%s-block-editor\" data-block-settings=\"%s\" class=\"postbox hidden\">", $this->name, $this->name );
        $html.= sprintf( "<h3 class=\"hndle\">%s</h3>", $this->title );
        $html.= "<div class=\"inside\"><div class=\"options\"><table class=\"form-table\">";

        foreach ( $block_settings as $i => $settings ){

            if ( isset( $settings[ 'type' ] ) && method_exists( WPGH()->html, $settings[ 'type' ] ) ){

                $html .= "<tr>";

                if ( isset( $settings[ 'label' ] ) ){

                    $html .= sprintf( "<th>%s</th>", $settings[ 'label' ] );

                }

                $html .= sprintf( "<td>%s</td>", call_user_func( array( WPGH()->html, $settings[ 'type' ] ), $settings[ 'atts' ] ) );

                $html .= "</tr>";

            }

        }

        $html .= '</table>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        echo $html;
    }

}