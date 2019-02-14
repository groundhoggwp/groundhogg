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

class WPGH_Link_Clicked extends WPGH_Funnel_Step
{

    /**
     * @var string
     */
    public $type    = 'link_click';

    /**
     * @var string
     */
    public $group   = 'benchmark';

    /**
     * @var string
     */
    public $icon    = 'link-clicked.png';

    /**
     * @var string
     */
    public $name    = 'Link Click';

    /**
     * Add the completion action
     *
     * WPGH_Form_Filled constructor.
     */
    public function __construct()
    {
        $this->name         = _x( 'Link Click', 'element_name', 'groundhogg' );
        $this->description  = _x( 'Runs whenever a special link is clicked and redirects the user to another page.', 'element_description', 'groundhogg' );

        parent::__construct();

        add_action( 'wpgh_link_clicked', array( $this, 'complete' ), 10, 2 );
    }

    /**
     * @param $step WPGH_Step
     */
    public function settings( $step )
    {

        $redirect_url = $step->get_meta(  'redirect_to' );

        ?>

        <table class="form-table">
            <tbody>
            <tr>
                <th><?php _e( 'Copy This Link:' ); ?></th>
                <td>
                    <input
                            onfocus="this.select()"
                            class="regular-text code"
                            value="<?php echo site_url( '/gh-tracking/link/click?id=' . $step->ID ); ?>"
                            readonly>
                    <p class="description"><?php _e( 'Paste this link in any email or page. Once a contact clicks it the benchmark will be completed and the contact will be redirected to the page set below.' ); ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <?php esc_attr_e( 'Redirect To', 'groundhogg' ); ?>
                </th>
                <td><?php

                    $args = array(
                        'type'      => 'url',
                        'id'        => $step->prefix( 'redirect_to' ),
                        'name'      => $step->prefix( 'redirect_to' ),
                        'title'     => __( 'Redirect To' ),
                        'value'     => $redirect_url
                    );

                    echo WPGH()->html->input( $args );

                    ?>
                    <p class="description">
                        <a href="#" data-target="<?php echo $step->prefix( 'redirect_to' ) ?>" id="<?php echo $step->prefix( 'add_link' ); ?>">
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
        if ( isset( $_POST[ $step->prefix( 'redirect_to' ) ] ) )
            $step->update_meta( 'redirect_to', esc_url_raw( $_POST[ $step->prefix(  'redirect_to' ) ] ) );
    }

    /**
     * @param $step WPGH_Step the step benchmark
     * @param $contact WPGH_Contact the contact
     */
    public function complete( $step, $contact )
    {
        if ( $step->can_complete( $contact ) ){

            $step->enqueue( $contact );
//            do_action( 'wpgh_process_queue' );

        }

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