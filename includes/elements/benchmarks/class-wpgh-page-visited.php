<?php
/**
 * Page Visited
 *
 * This will run whenever a page is visited
 *
 * @package     Elements
 * @subpackage  Elements/Benchmarks
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.9
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WPGH_Page_Visited extends WPGH_Funnel_Step
{

    /**
     * @var string
     */
    public $type    = 'page_visited';

    /**
     * @var string
     */
    public $group   = 'benchmark';

    /**
     * @var string
     */
    public $icon    = 'page-visited.png';

    /**
     * @var string
     */
    public $name    = 'Page Visited';

    /**
     * Add the completion action
     *
     * WPGH_Form_Filled constructor.
     */
    public function __construct()
    {

        $this->description = __( 'Runs whenever the specified page is visited.', 'groundhogg' );

        parent::__construct();

        add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );
        add_action( 'wp_ajax_wpgh_page_view', array( $this, 'complete' ) );
        add_action( 'wp_ajax_nopriv_wpgh_page_view', array( $this, 'complete' ) );
    }

    /**
     * Enqueue the scripts for the event runner process.
     * Appears on front-end & backend as it will be run by traffic to the site.
     */
    public function scripts()
    {
        wp_enqueue_script( 'wpgh-page-view', WPGH_PLUGIN_URL . 'assets/js/frontend.js' , array('jquery'), filemtime( WPGH_PLUGIN_DIR . 'assets/js/frontend.js' ) );
        wp_localize_script( 'wpgh-page-view', 'wpgh_ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
    }

    /**
     * @param $step WPGH_Step
     */
    public function settings( $step )
    {
        $match_type = $step->get_meta( 'match_type' );
        $match_url = $step->get_meta(  'url_match' );

        ?>

        <table class="form-table">
            <tbody>
            <tr>
                <th>
                    <?php esc_attr_e( 'Enter URL', 'groundhogg' ); ?>
                </th>
                <td><?php

                    $match_types = array(
                        'partial'   => __( 'Partial Match' ),
                        'exact'     => __( 'Exact Match' )
                    );

                    $args = array(
                        'id'        => $step->prefix( 'match_type' ),
                        'name'      => $step->prefix( 'match_type' ),
                        'options'   => $match_types,
                        'selected'  => $match_type
                    );

                    echo WPGH()->html->dropdown( $args );

                    $args = array(
                        'type'      => 'url',
                        'id'        => $step->prefix( 'url_match' ),
                        'name'      => $step->prefix( 'url_match' ),
                        'title'     => __( 'Match Url' ),
                        'value'     => $match_url
                    );

                    echo WPGH()->html->input( $args );

                    ?>
                    <p class="description">
                        <a href="#" data-target="<?php echo $step->prefix( 'url_match' ) ?>" id="<?php echo $step->prefix( 'add_link' ); ?>">
                            <?php _e( 'Insert Link' , 'groundhogg' ); ?>
                        </a>
                    </p>
                    <script>
                        jQuery(function($){
                            $('#<?php echo $step->prefix('add_link' ); ?>').linkPicker();
                        });
                    </script>
                </td>
            </tr>
        </table>

        <?php
    }

    /**
     * Save the step settings
     *
     * @param $step WPGH_Step
     */
    public function save( $step )
    {
        if ( isset( $_POST[ $step->prefix( 'match_type' ) ] ) )
            $step->update_meta( 'match_type', sanitize_key( $_POST[ $step->prefix(  'match_type' ) ] ) );

        if ( isset( $_POST[ $step->prefix( 'url_match' ) ] ) )
            $step->update_meta(  'url_match', esc_url_raw( $_POST[ $step->prefix(  'url_match' ) ] ) );
    }

    /**
     * Whenever a page is visited the benchmark.
     */
    public function complete()
    {

        if ( ! wp_doing_ajax() )
            return;

        $contact = WPGH()->tracking->get_contact();

        if ( ! $contact )
            die;

        $steps = WPGH()->steps->get_steps( array( 'step_type' => $this->type, 'step_group' => $this->group ) );

        if ( empty( $steps ) )
            die;

        foreach ( $steps as $step ) {

            $step = new WPGH_Step( $step->ID );

            if ( $step->can_complete( $contact ) ){

                $match_type = $step->get_meta( 'match_type' );
                $match_url  = $step->get_meta( 'url_match' );

                if ( $match_type === 'exact' ){
                    $is_page = wp_get_referer() === $match_url;
                } else {
                    $is_page = strpos( wp_get_referer(), $match_url ) !== false;
                }

                if ( $is_page ){

                    $step->enqueue( $contact );

                }
            }
        }

        wp_die();
    }

    /**
     * Process the tag applied step...
     *
     * @param $contact WPGH_Contact
     * @param $event WPGH_Event
     *
     * @return true
     */
    public function run( $contact, $event )
    {
        //do nothing...

        return true;
    }

}