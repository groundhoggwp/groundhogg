<?php
/**
 * Apply Note
 *
 * Apply a note to a contact through the funnel builder.
 *
 * @package     Elements
 * @subpackage  Elements/Actions
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.9
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class WPGH_Apply_Note extends WPGH_Funnel_Step
{

    /**
     * @var string
     */
    public $type    = 'apply_note';

    /**
     * @var string
     */
    public $group   = 'action';

    /**
     * @var string
     */
    public $icon    = 'apply-note.png';

    /**
     * @var string
     */
    public $name    = 'Apply Note';

    /**
     * @param $step WPGH_Step
     */
    public function settings( $step )
    {

        $note = $step->get_meta( 'note_text' );

        if ( ! $note )
            $note = __( "This contact is super awesome!", 'groundhogg' );

        ?>
        <table class="form-table">
            <tbody>
            <tr>
                <th>
                    <?php echo esc_html__( 'Note Text:', 'groundhogg' ); ?>
                </th>
                <?php $args = array(
                    'id'    => $step->prefix( 'note_text' ),
                    'name'  => $step->prefix( 'note_text' ),
                    'value' => $note,
                    'cols'  => 64,
                    'rows'  => 4
                ); ?>
                <td>
                    <?php echo WPGH()->html->textarea( $args ) ?>
                    <p class="description">
                        <?php _e( 'Use any valid replacement codes', 'groundhogg' ); ?>
                    </p>
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

        if ( isset( $_POST[ $step->prefix( 'note_text' ) ] ) ){

            $note_text = sanitize_textarea_field(  $_POST[ $step->prefix( 'note_text' ) ] );

            $step->update_meta( 'note_text', $note_text );

        }

    }

    /**
     * Process the apply note step...
     *
     * @param $contact WPGH_Contact
     * @param $event WPGH_Event
     *
     * @return true;
     */
    public function run( $contact, $event )
    {

        $note = $event->step->get_meta( 'note_text' );

        $finished_note = WPGH()->replacements->process( $note, $contact->ID );

        $contact->add_note( $finished_note );

        return true;

    }


}