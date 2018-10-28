<?php
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

if ( ! defined( 'ABSPATH' ) ) exit;

class WPGH_Apply_Owner extends WPGH_Funnel_Step
{

    /**
     * @var string
     */
    public $type    = 'apply_owner';

    /**
     * @var string
     */
    public $group   = 'action';

    /**
     * @var string
     */
    public $icon    = 'apply-owner.png';

    /**
     * @var string
     */
    public $name    = 'Apply Owner';

    /**
     * @param $step WPGH_Step
     */
    public function settings( $step )
    {

        $owner = $step->get_meta( 'owner_id' );

        if ( ! $owner )
            $owner = get_current_user_id();

        ?>
        <table class="form-table">
            <tbody>
            <tr>
                <th>
                    <?php echo esc_html__( 'Apply Owner:', 'groundhogg' ); ?>
                </th>
                <td>
                    <?php echo WPGH()->html->dropdown_owners( array(
                        'selected'  => $owner,
                        'name'      => $step->prefix( 'owner_id' ),
                        'id'        => $step->prefix( 'owner_id' ),
                    ) ); ?>
                </td>
            </tr>
            </tbody>
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

        if ( isset( $_POST[ $step->prefix( 'owner_id' ) ] ) ){

            $owner_id = intval(  $_POST[ $step->prefix( 'owner_id' ) ] );

            $step->update_meta( 'owner_id', $owner_id );

        }

    }

    /**
     * Process the apply owner step...
     *
     * @param $contact WPGH_Contact
     * @param $event WPGH_Event
     *
     * @return true
     */
    public function run( $contact, $event )
    {

        $owner = $event->step->get_meta( 'owner_id' );

        $owner = intval( $owner );

        if ( $owner ){

            $event->contact->update( array( 'owner_id' => $owner ) );

        }

        return true;

    }

}