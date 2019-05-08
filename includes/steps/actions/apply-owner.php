<?php
namespace Groundhogg\Steps\Actions;

use Groundhogg\Contact;
use Groundhogg\Event;
use Groundhogg\HTML;
use Groundhogg\Step;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Apply Owner
 *
 * Apply an owner through the funnel builder depending on the the funnel
 *
 * @package     Elements
 * @subpackage  Elements/Actions
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.9
 */
class Apply_Owner extends Action
{

    /**
     * Get the element name
     *
     * @return string
     */
    public function get_name()
    {
        return _x( 'Apply Owner', 'step_name', 'groundhogg' );
    }

    /**
     * Get the element type
     *
     * @return string
     */
    public function get_type()
    {
        return 'apply_owner';
    }

    /**
     * Get the description
     *
     * @return string
     */
    public function get_description()
    {
        return _x( 'Set the contact owner to the specified user account.', 'step_description', 'groundhogg' );
    }

    /**
     * Get the icon URL
     *
     * @return string
     */
    public function get_icon()
    {
        return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/apply-owner.png';
    }

    /**
     * @param $step Step
     */
    public function settings( $step )
    {

        $this->start_controls_section();

        $this->add_control( 'owner_id', [
            'label'         => __( 'Apply Owner:', 'groundhogg' ),
            'type'          => HTML::DROPDOWN_OWNERS,
            'default'       => "This contact is super awesome!",
            'description'   => __( 'Selecting more than 1 owner will create a round robin.', 'groundhogg' ),
            'multiple'  => true,
            'field'         => [
                'class'     => 'gh-select2',
            ],
        ] );

        $this->end_controls_section();
    }

    /**
     * Save the step settings
     *
     * @param $step Step
     */
    public function save( $step )
    {
        $this->save_setting( 'owner_id', wp_parse_id_list( $this->get_posted_data( 'owner_id', [] ) ) );
    }

    /**
     * Process the apply owner step...
     *
     * @param $contact Contact
     * @param $event Event
     *
     * @return true
     */
    public function run( $contact, $event )
    {

        $owner = $this->get_setting( 'owner_id' );
        $owner = is_array( $owner ) ? wp_parse_id_list( $owner ) : absint( $owner );

        /* backwards compat */
        if ( is_numeric( $owner ) ){

           $contact->update( ['owner_id' => $owner ] );

        } else if ( is_array( $owner ) ){

            if ( count( $owner ) === 1 ){

                $contact->update( [ 'owner_id' => $owner[0] ] );

            } else {

                $i = $this->get_setting( 'index', 0 );

                if ( $i >= count( $owner ) ){
                    $i = 0;
                }

                $contact->update( [ 'owner_id' => $owner[ $i ] ] );

                $i++;

                $this->save_setting( 'index', $i );

            }

        }

        return true;

    }
}