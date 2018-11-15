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
    public $name    = 'Web Form';

    /**
     * Add the completion action
     *
     * WPGH_Form_Filled constructor.
     */
    public function __construct()
    {
        $this->description = __( 'Use this form builder to create forms and display them on your site with shortcodes.', 'groundhogg' );

        parent::__construct();

        add_action( 'wpgh_form_submit', array( $this, 'complete' ), 10, 3 );

        if ( is_admin() && isset( $_GET['page'] ) && $_GET[ 'page' ] === 'gh_funnels' && isset($_REQUEST[ 'action' ]) && $_REQUEST[ 'action' ] === 'edit' ){
            add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ));
        }

        add_action( 'wp_ajax_wpgh_form_impression', array( $this, 'track_impression' ) );
        add_action( 'wp_ajax_nopriv_wpgh_form_impression', array( $this, 'track_impression' ) );
    }

    /**
     * Enqueue the form builder JS in the admin area
     */
    public function scripts()
    {

        wp_enqueue_script( 'wpgh-form-builder', WPGH_ASSETS_FOLDER . 'js/admin/form-builder.js', array(), filemtime( WPGH_PLUGIN_DIR . 'assets/js/admin/form-builder.js' ) );

    }

    /**
     * Track a form impression from the frontend.
     */
    public function track_impression()
    {

        if( !class_exists( 'Browser' ) )
            require_once WPGH_PLUGIN_DIR . 'includes/lib/browser.php';

        $browser = new Browser();
        if ( $browser->isRobot() || $browser->isAol() ){
            wp_die( json_encode( array( 'error' => 'No Track Robots.' ) ) );
        }

        $ID = intval( $_POST[ 'id' ] );

        if ( ! WPGH()->steps->exists( $ID ) ){
            wp_die( json_encode( array( 'error' => 'Form DNE.' ) ) );
        }

        $step = new WPGH_Step( $ID );

        $response = array();

        /*
         * Is Contact
         */
        if ( $contact = WPGH()->tracking->get_contact() ) {

            $db = WPGH()->activity;

            /* Check if impression for contact exists... */
            $args = array(
                'funnel_id'     => $step->funnel_id,
                'step_id'       => $step->ID,
                'contact_id'    => $contact->ID,
                'activity_type' => 'form_impression',
            );

            $response[ 'cid' ] = $contact->ID;

        } else {
            /*
            * Not a Contact
            */

            /* validate against viewers IP? Cookie? TBD */
            $db = WPGH()->activity;

            /* Check if impression for contact exists... */
            if ( isset( $_COOKIE[ 'gh_ref_id' ] ) ){
                $ref_id = sanitize_key( $_COOKIE[ 'gh_ref_id' ] );
            } else {
                $ref_id = uniqid( 'g' );
            }

            $args = array(
                'funnel_id'     => $step->funnel_id,
                'step_id'       => $step->ID,
                'activity_type' => 'form_impression',
                'ref'           => $ref_id
            );

            $response[ 'ref_id' ] = $ref_id;

        }

        if ( ! $db->activity_exists( $args ) ){

            $args[ 'timestamp' ] = time();
            $db->add( $args );

            $response[ 'result' ] = 'success';

        }

        wp_die( json_encode( $response ) );

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
                        </a>
                    </p>
                    <script>
                        jQuery(function($){
                            $('#<?php echo $step->prefix('add_link' ); ?>').linkPicker();
                        });
                    </script>
                </td>
            </tr>
            </tbody>
        </table>
        <table>
            <tbody>
            <tr>
                <td>
                    <div class="form-editor">
                        <div class="form-buttons">
                            <?php

                            $buttons = array(
                                array(
                                    'text' => __( 'First' ),
                                    'class' => 'button button-secondary first'
                                ),
                                array(
                                    'text' => __( 'Last' ),
                                    'class' => 'button button-secondary last'

                                ),
                                array(
                                    'text' => __( 'Email' ),
                                    'class' => 'button button-secondary email'

                                ),
                                array(
                                    'text' => __( 'Phone' ),
                                    'class' => 'button button-secondary phone'

                                ),
                                array(
                                    'text' => __( 'GDPR' ),
                                    'class' => 'button button-secondary gdpr'

                                ),
                                array(
                                    'text' => __( 'Terms' ),
                                    'class' => 'button button-secondary terms'

                                ),
                                array(
                                    'text' => __( 'ReCaptcha' ),
                                    'class' => 'button button-secondary recaptcha'

                                ),
                                array(
                                    'text' => __( 'Submit' ),
                                    'class' => 'button button-secondary submit-button'

                                ),
                                array(
                                    'text' => __( 'Text' ),
                                    'class' => 'button button-secondary text'

                                ),
                                array(
                                    'text' => __( 'Textarea' ),
                                    'class' => 'button button-secondary textarea'

                                ),
                                array(
                                    'text' => __( 'Number' ),
                                    'class' => 'button button-secondary number'

                                ),
                                array(
                                    'text' => __( 'Dropdown' ),
                                    'class' => 'button button-secondary dropdown'

                                ),
                                array(
                                    'text' => __( 'Radio' ),
                                    'class' => 'button button-secondary radio'

                                ),
                                array(
                                    'text' => __( 'Checkbox' ),
                                    'class' => 'button button-secondary checkbox'

                                ),
                                array(
                                    'text' => __( 'Address' ),
                                    'class' => 'button button-secondary address'

                                ),

	                            array(
		                            'text' => __( 'Row' ),
		                            'class' => 'button button-secondary row'

	                            ),

	                            array(
		                            'text' => __( 'Col' ),
		                            'class' => 'button button-secondary column'

	                            ),
                            );

                            foreach ( $buttons as $button ){
                                echo WPGH()->html->button( $button );
                            } ?>
                        </div>

                        <?php

                        $args = array(
                            'id'    => $step->prefix( 'form' ),
                            'name'  => $step->prefix( 'form' ),
                            'value' => $form,
                            'class' => 'code form-html',
                            'cols'  => 64,
                            'rows'  => 4
                        ); ?>

                        <?php echo WPGH()->html->textarea( $args ) ?>
                        <p class="description">
                            <?php _e( 'The form editor is a work in progress, to learn how to build forms for now go <a target="_blank" href="https://www.groundhogg.io/2018/09/27/update-to-the-form-shortcode/">here</a>.', 'groundhogg' ); ?>
                        </p>
                    </div>
                </td>
            </tr>
        </table>

        <?php
    }


    /**
     * Extend the Form reporting VIEW with impressions vs. submissions...
     *
     * @param $step WPGH_Step
     */
    public function reporting($step)
    {
        $start_time = WPGH()->menu->funnels_page->reporting_start_time;
        $end_time   = WPGH()->menu->funnels_page->reporting_end_time;

        $cquery = new WPGH_Contact_Query();

        $num_events_completed = $cquery->query( array(
            'count' => true,
            'report' => array(
                'start' => $start_time,
                'end'   => $end_time,
                'step'  => $step->ID,
                'funnel'=> $step->funnel_id,
                'status'=> 'complete'
            )
        ) );

        $num_impressions = WPGH()->activity->count(array(
            'start'     => $start_time,
            'end'       => $end_time,
            'step_id'   => $step->ID,
            'activity_type' => 'form_impression'
        ));

        ?>
        <p class="report">
            <span class="impressions"><?php _e( 'Views: '); ?>
                <strong>
                    <?php echo $num_impressions; ?>
                </strong>
            </span> |
                <span class="submissions"><?php _e( 'Fills: ' ); ?><strong><a target="_blank" href="<?php echo admin_url( 'admin.php?page=gh_contacts&view=report&status=complete&funnel=' . $step->funnel_id . '&step=' . $step->ID . '&start=' . $start_time . '&end=' . $end_time ); ?>"><?php echo $num_events_completed; ?></a></strong></span> |
            <span class="cvr" title="<?php _e( 'Conversion Rate' ); ?>"><?php _e( 'CVR: '); ?><strong><?php echo round( ( $num_events_completed / ( ( $num_impressions > 0 )? $num_impressions : 1 ) * 100 ), 2 ); ?></strong>%</span>
        </p>
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

            $step->update_meta( 'form', wp_kses_post( $_POST[  $step->prefix( 'form' ) ] ) );

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