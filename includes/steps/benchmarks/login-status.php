<?php
namespace Groundhogg\Steps\Benchmarks;

use Groundhogg\Contact;
use function Groundhogg\create_contact_from_user;
use Groundhogg\Plugin;
use Groundhogg\Step;

if ( ! defined( 'ABSPATH' ) ) exit;

class Login_Status extends Benchmark
{

    /**
     * Get the element name
     *
     * @return string
     */
    public function get_name()
    {
        return _x( 'Logs In', 'step_name', 'groundhogg' );
    }

    /**
     * Get the element type
     *
     * @return string
     */
    public function get_type()
    {
        return 'login_status';
    }

    /**
     * Get the description
     *
     * @return string
     */
    public function get_description()
    {
        return _x( 'Whenever a user logs in for a certain amount of times.', 'step_description', 'groundhogg' );
    }

    /**
     * Get the icon URL
     *
     * @return string
     */
    public function get_icon()
    {
        return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/login-status.png';
    }

    /**
     * @param $step Step
     */
    public function settings( $step )
    {

        $html = Plugin::$instance->utils->html;

        $html->start_form_table();

        $html->start_row();

        $html->th(
            __( 'Run when a user logs in:', 'groundhogg' )
        );

        $html->td( [
            $html->dropdown( [
                'name'  => $this->setting_name_prefix( 'type' ),
                'id'    => $this->setting_id_prefix( 'type' ),
                'class'   => '',
                'options' => array(
                    'any'   => __( 'Any Time', 'groundhogg' ),
                    'times' => __( 'Number of Times', 'groundhogg' ),
                ),
                'selected' => $this->get_setting( 'type', 'any' ),
                'multiple' => false,
            ] ),
            $html->number( [
                'name'  => $this->setting_name_prefix( 'amount' ),
                'id'    => $this->setting_id_prefix( 'amount' ),
                'value' => $this->get_setting( 'amount' ),
                'class' => 'input'
            ] ),
            sprintf( '<script>
            jQuery( function($){
               $( \'#%1$s\' ).on( \'change\', function ( e ) {
                   if ( $(this).val() === \'any\' ){$( \'#%2$s\' ).hide();}
                   else {$( \'#%2$s\' ).show();}
               } );$( \'#%1$s\' ).trigger( \'change\' );
            });</script>', $this->setting_id_prefix( 'type' ), $this->setting_id_prefix( 'amount' ) )
        ] );

        $html->end_row();

        $html->end_form_table();
    }

    /**
     * Save the step settings
     *
     * @param $step Step
     */
    public function save( $step )
    {
        $this->save_setting( 'type', sanitize_text_field( $this->get_posted_data( 'type', 'any' ) ) );
        $this->save_setting( 'amount', absint( $this->get_posted_data( 'amount', 1 ) ) );
    }

    /**
     * get the hook for which the benchmark will run
     *
     * @return int[]
     */
    protected function get_complete_hooks()
    {
        return [ 'wp_login' => 2 ];
    }

    /**
     * @param $user_login
     * @param $user \WP_User
     */
    public function setup( $user_login, $user ){
        $this->add_data(  'user_id', $user->ID );
        $contact = create_contact_from_user( $this->get_data( 'user_id' ) );

        if ( is_wp_error( $contact ) || ! $contact ){
            return;
        }

        $times = absint( $contact->get_meta( 'times_logged_in' ) );
        $times++;
        /* Update the number of times logged in */
        $contact->update_meta( 'times_logged_in', $times );
        $this->set_current_contact( $contact );
    }

    /**
     * Get the contact from the data set.
     *
     * @return Contact
     */
    protected function get_the_contact()
    {
        return $this->get_current_contact();
    }

    /**
     * Based on the current step and contact,
     *
     * @return bool
     */
    protected function can_complete_step()
    {
        $times = absint( $this->get_current_contact()->get_meta( 'times_logged_in' ) );
        return ( $this->get_setting( 'type' ) === 'any' ) ? $can_complete = true : $times === $this->get_setting( 'amount' );
    }
}