<?php
namespace Groundhogg\Admin\Emails\Blocks;

use function Groundhogg\array_to_css;
use Groundhogg\Plugin;
/**
 * Email block
 *
 * Basic Email Block Template
 * Not many people no JS yet, so we're going to go with a standard PHP email block api system.
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

abstract class Block
{

    /**
     * This should be used to enqueue any js files that would act as settings for the
     * block...
     *
     * WPGH_Email_Block constructor.
     */
    public function __construct()
    {
        add_action( "admin_enqueue_scripts", [ $this, 'scripts' ] );
        add_filter( 'groundhogg/admin/emails/blocks', [ $this, 'register' ] );
        add_action( "groundhogg/admin/emails/blocks/{$this->get_name()}/settings_panel", [ $this, 'settings_panel' ] );
        add_action( "groundhogg/admin/emails/blocks/{$this->get_name()}/html" , [ $this, 'block_html' ] );
        add_action( "groundhogg/admin/emails/blocks/{$this->get_name()}/extra_css", [ $this, 'extra_css' ] );
    }

    abstract public function get_icon();
    abstract public function get_name();
    abstract public function get_title();
    abstract public function get_settings();
    abstract public function scripts();

    public function extra_css( $css ){ return $css; }

    /**
     * @var array()
     */
    protected $settings = [];

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

            $blocks[ $this->get_name() ][ 'icon' ]  = $this->get_icon();
            $blocks[ $this->get_name() ][ 'title' ] = $this->get_title();
            $blocks[ $this->get_name() ][ 'name' ]  = $this->get_name();

        }

        return $blocks;

    }

    /**
     * Gets the full block html
     */
    public function block_html()
    {

        $html = sprintf( "<div  class=\"row\" data-block='%s'>", $this->get_name() );
        $html.= sprintf( "<div  class=\"content-wrapper %s_block\">" , $this->get_name() );

        $extra_css = array_to_css( apply_filters( "groundhogg/admin/emails/blocks/{$this->get_name()}/extra_css", [] ) );

        $html.= "<div class=\"content-inside inner-content text-content\" style=\"padding: 5px;$extra_css\">";

        $html .= $this->inner_html();

        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        echo $html;

    }

    /**
     * @return mixed
     */
    abstract protected function inner_html();

    /**
     * Register the settings for this particular block.
     * It uses an api to the HTML helper class...
     *
     * @see WPGH_HTML
     * @return array
     */
    protected function register_settings(){
        return apply_filters( "groundhogg/admin/emails/blocks/{$this->get_name()}/settings", $this->get_settings() );
    }

    /**
     * Build the settings panel for the block
     */
    public function settings_panel()
    {

        $block_settings = $this->register_settings();

        $html = sprintf( '<div id="%1$s-block-editor" data-block-settings="%1$s" class="postbox hidden">', $this->get_name() );
        $html.= sprintf( "<h3 class=\"hndle\">%s</h3>", $this->get_title() );
        $html.= "<div class=\"inside\"><div class=\"options\"><table class=\"form-table\">";

        foreach ( $block_settings as $i => $settings ){

            if ( isset( $settings[ 'type' ] ) && method_exists( Plugin::$instance->utils->html, $settings[ 'type' ] ) ){

                $html .= "<tr>";

                if ( isset( $settings[ 'label' ] ) ){

                    $html .= sprintf( "<th>%s</th>", $settings[ 'label' ] );

                }

                $html .= sprintf( "<td>%s</td>", call_user_func( array( Plugin::$instance->utils->html, $settings[ 'type' ] ), $settings[ 'atts' ] ) );

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