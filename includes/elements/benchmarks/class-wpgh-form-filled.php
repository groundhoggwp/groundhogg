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
        $shortcode = sprintf('[gh_form id="%d" success="%s"]', $step->ID, esc_url( site_url( '/thank-you/' ) ) );
        $shortcode .= '[gh_first_name]';
        $shortcode .= '[gh_last_name]';
        $shortcode .= '[gh_email]';
        $shortcode .= '[gh_phone]';
        $shortcode .= '[gh_terms]';

        if ( wpgh_is_gdpr() )
            $shortcode .= '[gh_gdpr]';

        if ( wpgh_is_recaptcha_enabled() )
            $shortcode .= '[gh_recaptcha]';

        $shortcode .= '[gh_submit]Submit[/gh_submit]';
        $shortcode .= '[/gh_form]';

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
                        <textarea
                                onfocus="this.select()"
                                class="regular-text code"
                                readonly><?php echo $shortcode; ?></textarea>
                        </strong>
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
        //code is poetry...
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