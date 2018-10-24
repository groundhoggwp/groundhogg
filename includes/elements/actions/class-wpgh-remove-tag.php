<?php
/**
 * Remove Tag
 *
 * This will remove any specified tags from the contact
 *
 * @package     Elements
 * @subpackage  Elements/Actions
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.9
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WPGH_Remove_Tag extends WPGH_Funnel_Step
{

    /**
     * @var string
     */
    public $type    = 'remove_tag';

    /**
     * @var string
     */
    public $group   = 'action';

    /**
     * @var string
     */
    public $icon    = 'remove-tag.png';

    /**
     * @var string
     */
    public $name    = 'Remove Tag';

    /**
     * @param $step WPGH_Step
     */
    public function settings( $step )
    {

        $tags = $step->get_meta( 'tags' );

        if ( ! $tags )
            $tags = array();

        ?>
        <table class="form-table">
            <tbody>
            <tr>
                <th>
                    <?php echo esc_html__( 'Remove These Tags:', 'groundhogg' ); ?>
                </th>
                <?php $args = array(
                    'id' => $step->prefix( 'tags' ),
                    'name' => $step->prefix( 'tags' ) . '[]',
                    'selected' => $tags
                ); ?>
                <td>
                    <?php echo WPGH()->html->tag_picker( $args ); ?>
                    <p class="description"><?php _e( 'Add new tags by hitting [enter] or by typing a [comma].', 'groundhogg' ); ?></p>
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

        if ( isset( $_POST[ $step->prefix( 'tags' ) ] ) ){

            $tags = WPGH()->tags->validate( $_POST[ $step->prefix( 'tags' ) ] );

            $step->update_meta( 'tags', $tags );

        }

    }

    /**
     * Process the remove tag step...
     *
     * @param $contact WPGH_Contact
     * @param $event WPGH_Event
     *
     * @return true
     */
    public function run( $contact, $event )
    {
        $tags = $event->step->get_meta( 'tags' );

        return $contact->remove_tag( $tags );
    }

    /**
     * @param array $args
     * @param WPGH_Step $step
     */
    public function import($args, $step)
    {
        $tags = WPGH()->tags->validate( $args[ 'tags' ] );

        $step->update_meta( 'tags', $tags );
    }

    /**
     * @param array $args
     * @param WPGH_Step $step
     * @return array
     */
    public function export($args, $step)
    {
        $args['tags'] = array();

        $tags = $step->get_meta( 'tags' );

        if ( empty( $tags ) )
            return $args;

        foreach ( $tags as $tag_id ) {

            $tag = WPGH()->tags->get_tag( $tag_id );

            if ( $tag ){
                $args[ 'tags' ][] = $tag->tag_name;
            }

        }

        return $args;
    }

}