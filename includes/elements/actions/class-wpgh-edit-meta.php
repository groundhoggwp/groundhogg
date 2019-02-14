<?php
/**
 * Edit Meta
 *
 * This allows the user to add information to a contact depeding on where they are in their customer journey. Potentially using them as merge fields later on.
 *
 * @package     Elements
 * @subpackage  Elements/Actions
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.9
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WPGH_Edit_Meta extends WPGH_Funnel_Step
{

    /**
     * @var string
     */
    public $type    = 'edit_meta';

    /**
     * @var string
     */
    public $group   = 'action';

    /**
     * @var string
     */
    public $icon    = 'edit-meta.png' ;

    /**
     * @var string
     */
    public $name    = 'Edit Meta';

    /**
     * @var string
     */
    public $description = 'Directly edit the meta data of the contact.';

    public function __construct()
    {
        $this->name = _x( 'Edit Meta', 'element_name', 'groundhogg' );
        $this->description = _x( 'Directly edit the meta data of the contact.', 'element_description', 'groundhogg' );

        parent::__construct();
    }

    /**
     * Display the settings
     *
     * @param $step WPGH_Step
     */
    public function settings( $step )
    {
        $post_keys      = $step->get_meta( 'meta_keys' );
        $post_values    = $step->get_meta( 'meta_values' );

        if ( ! is_array( $post_keys ) || ! is_array( $post_values ) ){
            $post_keys = array( '' ); //empty to show first option.
            $post_values = array( '' ); //empty to show first option.
        }

        ?>

        <table class="form-table" id="meta-table-<?php echo $step->ID ; ?>">
            <tbody>
            <?php foreach ( $post_keys as $i => $post_key): ?>
                <tr>
                    <td>
                        <label><strong><?php _e( 'Key: ' ); ?></strong>

                            <?php $args = array(
                                'name'  => $step->prefix( 'meta_keys' ) . '[]',
                                'class' => 'input',
                                'value' => sanitize_key( $post_key )
                            );

                            echo WPGH()->html->input( $args ); ?>

                        </label>
                    </td>
                    <td>
                        <label><strong><?php _e( 'Value: ' ); ?></strong> <?php $args = array(
                                'name'  => $step->prefix( 'meta_values' ) . '[]',
                                'class' => 'input',
                                'value' => esc_html( $post_values[$i] )
                            );

                            echo WPGH()->html->input( $args ); ?></label>
                    </td>
                    <td>
                    <span class="row-actions">
                        <span class="add"><a style="text-decoration: none" href="javascript:void(0)" class="addmeta"><span class="dashicons dashicons-plus"></span></a></span> |
                        <span class="delete"><a style="text-decoration: none" href="javascript:void(0)" class="deletemeta"><span class="dashicons dashicons-trash"></span></a></span>
                    </span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <script>
            jQuery(function($){
                var table = $( "#meta-table-<?php echo $step->ID; ?>" );
                table.click(function ( e ){
                    var el = $(e.target);
                    if ( el.closest( '.addmeta' ).length ) {
                        el.closest('tr').last().clone().appendTo( el.closest('tr').parent() );
                        el.closest('tr').parent().children().last().find( ':input' ).val( '' );
                    } else if ( el.closest( '.deletemeta' ).length ) {
                        el.closest( 'tr' ).remove();
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * Save the settings
     *
     * @param $step WPGH_Step
     */
    public function save( $step )
    {

        if ( isset( $_POST[ $step->prefix(  'meta_keys' ) ]  ) ){
            $post_keys = $_POST[ $step->prefix(  'meta_keys' ) ];
            $post_values = $_POST[ $step->prefix( 'meta_values' ) ];

            if ( ! is_array( $post_keys ) )
                return;

            $post_keys = array_map( 'sanitize_key', $post_keys );
            $post_values = array_map( 'sanitize_text_field', $post_values );

            $step->update_meta( 'meta_keys', $post_keys );
            $step->update_meta( 'meta_values', $post_values );
        }

    }

    /**
     * Process the http post step...
     *
     * @param $contact WPGH_Contact
     * @param $event WPGH_Event
     *
     * @return bool|object
     */
    public function run( $contact, $event )
    {

        $meta_keys = $event->step->get_meta(  'meta_keys' );
        $meta_values = $event->step->get_meta( 'meta_values' );

        if ( ! is_array( $meta_keys ) || ! is_array( $meta_values ) || empty( $meta_keys ) || empty( $meta_values ) ){
            return false;
        }

        foreach ( $meta_keys as $i => $meta_key ){
            $contact->update_meta( sanitize_key( $meta_key ), sanitize_text_field( WPGH()->replacements->process( $meta_values[ $i ], $contact->ID ) ) );
        }

        return true;

    }


}