<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-10-04
 * Time: 5:42 PM
 */

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
            $owner = __( "This contact is super awesome!", 'groundhogg' );

        ?>
        <table class="form-table">
            <tbody>
            <tr>
                <th>
                    <?php echo esc_html__( 'Note Text:', 'groundhogg' ); ?>
                </th>
                <?php $args = array(
                    'show_option_none' => __( 'Select an owner' ),
                    'id' => $step->prefix( 'owner_id' ),
                    'name' => $step->prefix( 'owner_id' ),
                    'role' => 'administrator',
                    'selected' => $owner
                ); ?>
                <td>
                    <?php wp_dropdown_users( $args ); ?>
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