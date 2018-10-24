<?php
/**
 * Form Filled
 *
 * This will run whenever a form is completed
 *
 * @package     Elements
 * @subpackage  Elements/Benchmarks
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.9
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WPGH_Form_Filled extends WPGH_Funnel_Step
{

    /**
     * @var string
     */
    public $type    = 'form_fill';

    /**
     * @var string
     */
    public $group   = 'benchmark';

    /**
     * @var string
     */
    public $icon    = 'form-filled.png';

    /**
     * @var string
     */
    public $name    = 'Form Filled';

    /**
     * Add the completion action
     *
     * WPGH_Form_Filled constructor.
     */
    public function __construct()
    {
        parent::__construct();

        add_action( 'wpgh_form_submit', array( $this, 'complete' ), 10, 3 );
    }

    /**
     * @param $step WPGH_Step
     */
    public function settings( $step )
    {
        $shortcode = sprintf('[gh_form id="%d" title="%s"]', $step->ID, $step->title );


        $form = $step->get_meta( 'form' );

        if ( empty( $form ) ){
            $form = "[first_name]\n";
            $form .= "[last_name]\n";
            $form .= "[email]\n";
            $form .= "[phone]\n";
            $form .= "[terms]\n";

            if ( wpgh_is_gdpr() )
                $form .= "[gdpr]\n";

            if ( wpgh_is_recaptcha_enabled() )
                $form .= "[recaptcha]\n";

            $form .= "[submit]Submit[/submit]";
        }

        $ty_page = $step->get_meta( 'success_page' );

        if ( empty( $ty_page ) ){
            $ty_page = site_url( 'thank-you/' );
        }

        ?>

        <table class="form-table">
            <tbody>
            <tr>
                <th>
                    <?php esc_attr_e( 'Shortcode:', 'groundhogg' ); ?>
                </th>
                <td>
                    <p>
                        <strong>
                        <input
                                onfocus="this.select()"
                                class="regular-text code"
                                value="<?php echo esc_attr( $shortcode ); ?>"
                                readonly>
                        </strong>
                    </p>
                </td>
            </tr><tr>
                <th>
                    <?php esc_attr_e( 'Thank You Page:', 'groundhogg' ); ?>
                </th>
                <td>
                    <?php

                    $args = array(
                        'type'      => 'text',
                        'id'        => $step->prefix( 'success_page' ),
                        'name'      => $step->prefix( 'success_page' ),
                        'title'     => __( 'Thank You Page' ),
                        'value'     => $ty_page
                    );

                    echo WPGH()->html->input( $args );

                    ?>
                    <p class="description">
                        <a href="#" data-target="<?php echo $step->prefix( 'success_page' ) ?>" id="<?php echo $step->prefix( 'add_link' ); ?>">
                            <?php _e( 'Insert Link' , 'groundhogg' ); ?>
                        </a> | <?php _e('Does not match query string.', 'groundhogg' ); ?>
                    </p>
                    <script>
                        jQuery(function($){
                            $('#<?php echo $step->prefix('add_link' ); ?>').linkPicker();
                        });
                    </script>
                </td>
            </tr>
            <tr>
                <th>
                    <?php esc_attr_e( 'Fields:', 'groundhogg' ); ?>
                </th>
                <td>
                    <?php $args = array(
                        'id'    => $step->prefix( 'form' ),
                        'name'  => $step->prefix( 'form' ),
                        'value' => $form,
                        'class' => 'code',
                        'cols'  => 50,
                        'rows'  => 4
                    ); ?>
                    <?php echo WPGH()->html->textarea( $args ) ?>
                    <p class="description">
                        <?php _e( 'The form editor is a work in progress, to learn how to build forms for now go <a target="_blank" href="https://www.groundhogg.io/2018/09/27/update-to-the-form-shortcode/">here</a>.', 'groundhogg' ); ?>
                    </p>
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
        if ( isset( $_POST[ $step->prefix( 'success_page' ) ] ) ){

            $step->update_meta( 'success_page', esc_url_raw( $_POST[  $step->prefix( 'success_page' ) ] ) );

        }

        if ( isset( $_POST[ $step->prefix( 'form' ) ] ) ){

            $step->update_meta( 'form', sanitize_textarea_field( $_POST[  $step->prefix( 'form' ) ] ) );

        }
    }

    /**
     * Whenever a form is filled complete the benchmark.
     *
     * @param $step_id
     * @param $contact WPGH_Contact
     * @param $submission int
     *
     * @return bool
     */
    public function complete( $step_id, $contact, $submission )
    {

	    $step = new WPGH_Step( $step_id );

	    /* Double check that the wpgh_form_submit action isn't being fired by another benchmark */
	    if ( $step->type !== $this->type )
	        return false;

	    $success = false;

	    if ( $step->can_complete( $contact ) ){

		    $success = $step->enqueue( $contact );

	    }

	    /*var_dump( $success );
	    wp_die( 'made-it-here' );*/

	    return $success;

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