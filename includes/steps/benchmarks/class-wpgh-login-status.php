<?php
/**
 * Account Created
 *
 * This will run proceeding actions whenever a WordPRess acount is created
 *
 * @package     Elements
 * @subpackage  Elements/Benchmarks
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.9
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WPGH_Login_Status_Changed extends WPGH_Funnel_Step
{

    /**
     * @var string
     */
    public $type    = 'login_status';

    /**
     * @var string
     */
    public $group   = 'benchmark';

    /**
     * @var string
     */
    public $icon    = 'login-status.png';

    /**
     * @var string
     */
    public $name    = 'Login/Logout';

    /**
     * Add the completion action
     *
     * WPGH_Form_Filled constructor.
     */
    public function __construct()
    {

        $this->name         = _x( 'Logged In', 'element_name', 'groundhogg' );
        $this->description  = _x( 'Whenever a user logs in for a certain amount of times.', 'element_description', 'groundhogg' );

        parent::__construct();

        add_action( 'wp_login', array( $this, 'login' ), 10, 2 );
    }

    /**
     * @param $step WPGH_Step
     */
    public function settings( $step )
    {
        $type = $step->get_meta( 'type' );
        $amount = $step->get_meta( 'amount' );

        if ( ! $type )
	        $type = 'any';

        if ( ! $amount )
	        $amount = 1;

        ?>

        <table class="form-table">
            <tbody>
            <tr>
                <th><?php echo esc_html__( 'Run when a user logs in:', 'groundhogg' ); ?></th>
                <td>
                    <?php echo WPGH()->html->dropdown(array(
	                    'name'  => $step->prefix( 'type' ),
	                    'id'    => $step->prefix( 'type' ),
	                    'class'   => '',
	                    'options' => array(
                            'any'   => __( 'Any Time', 'groundhogg' ),
                            'times' => __( 'Number of Times', 'groundhogg' ),
                        ),
	                    'selected' => $type,
	                    'multiple' => false,
                    )); ?>
                    <?php echo WPGH()->html->number( array(
	                    'name'  => $step->prefix( 'amount' ),
	                    'id'    => $step->prefix( 'amount' ),
	                    'value' => $amount,
                        'class' => 'input'
                    )); ?>
                </td>
            </tr>
            </tbody>
        </table>
        <script>
            jQuery( function($){
               $( '#<?php echo $step->prefix('type' ); ?>' ).on( 'change', function ( e ) {
                   if ( $(this).val() === 'any' ){$( '#<?php echo $step->prefix('amount' ); ?>' ).hide();}
                   else {$( '#<?php echo $step->prefix('amount' ); ?>' ).show();}
               } );$( '#<?php echo $step->prefix('type' ); ?>' ).trigger( 'change' );
            });
        </script>
        <?php
    }

    /**
     * Save the step settings
     *
     * @param $step WPGH_Step
     */
    public function save( $step )
    {
        if ( isset(  $_POST[ $step->prefix( 'type' ) ] ) ){
	        $status = sanitize_text_field( $_POST[ $step->prefix( 'type' ) ] );
            $step->update_meta( 'type', $status );
        }

        if ( isset(  $_POST[ $step->prefix( 'amount' ) ] ) ){
            $status = absint( $_POST[ $step->prefix( 'amount' ) ] );
            $step->update_meta( 'amount', $status );
        }

    }

	/**
     * Runs whenever a user logs in or logs out.
     *
	 * @param $user_login string
	 * @param $user WP_User
	 */
    public function login( $user_login, $user  )
    {
        $steps = $this->get_like_steps();
        $contact = wpgh_get_contact( $user->user_email );

        if ( ! $contact ){
            return;
        }

        /* Get times logged in so far */
        $times = $contact->get_meta( 'times_logged_in' ) ? $contact->get_meta( 'times_logged_in' ) : 0;
        $times++;
        /* Update the number of times logged in */
        $contact->update_meta( 'times_logged_in', $times );

        foreach ( $steps as $step ) {
            $status = $step->get_meta( 'type' );
	        $can_complete = ( $status === 'any' ) ? $can_complete = true : $times === $step->get_meta( 'amount' );

            if ( $step->can_complete( $contact ) && $can_complete ){
                $step->enqueue( $contact );
            }
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