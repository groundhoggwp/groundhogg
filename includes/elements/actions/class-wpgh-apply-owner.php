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
     * @var string
     */
    public $description = 'Set the contact owner to the specified user account';

    public function __construct()
    {
        $this->name = _x( 'Apply Owner', 'element_name', 'groundhogg' );
        $this->description = _x( 'Set the contact owner to the specified user account.', 'element_description', 'groundhogg' );

        parent::__construct();
    }

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
                    <?php echo esc_html_x( 'Apply Owner:', 'apply_owner_settings', 'groundhogg' ); ?>
                </th>
                <td>
                    <?php echo WPGH()->html->dropdown_owners( array(
                        'selected'  => $owner,
                        'multiple'  => true,
                        'class'     => 'gh-select2',
                        'name'      => $step->prefix( 'owner_id[]' ),
                        'id'        => $step->prefix( 'owner_id' ),
                    ) ); ?>
                    <p class="description"><?php echo esc_html_x( 'Selecting more than 1 owner will treat the step as a round robin.', 'apply_owner_settings', 'groundhogg' ) ?></p>
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

            $owner_id = array_map( 'intval', $_POST[ $step->prefix( 'owner_id' ) ] );

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

        /* backwards compat */
        if ( is_numeric( $owner ) ){

            $event->contact->update( array( 'owner_id' => intval( $owner ) ) );

        } else if ( is_array( $owner ) ){

            if ( count( $owner ) === 1 ){

                $event->contact->update( array( 'owner_id' => intval( $owner[0] ) ) );

            } else {

                $i = $event->step->get_meta( 'index' );

                if ( ! $i || $i >= count( $owner ) ){

                    $i = 0;

                }

                $event->contact->update( array( 'owner_id' => intval( $owner[ $i ] ) ) );

                $i++;

                $event->step->update_meta( 'index', $i );

            }

        }

        return true;

    }

}