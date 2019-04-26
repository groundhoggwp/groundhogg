<?php
/**
 * Tag removed
 *
 * This will run whenever a tag is removed from a contact
 *
 * @package     Elements
 * @subpackage  Elements/Benchmarks
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.9
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WPGH_Tag_Removed extends WPGH_Funnel_Step
{

    /**
     * @var string
     */
    public $type    = 'tag_removed';

    /**
     * @var string
     */
    public $group   = 'benchmark';

    /**
     * @var string
     */
    public $icon    = 'tag-removed.png';

    /**
     * @var string
     */
    public $name    = 'Tag Removed';

    /**
     * Add the completion action
     *
     * WPGH_Tag_Applied constructor.
     */
    public function __construct()
    {
        $this->name         = _x( 'Tag Removed', 'element_name', 'groundhogg' );
        $this->description  = _x( 'Runs whenever any of the specified tags are removed from a contact.', 'element_description', 'groundhogg' );

        parent::__construct();

        add_action( 'wpgh_tag_removed', array( $this, 'complete' ), 10, 2 );
    }

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
                    <?php
                    $args = [
                        'name' => $step->prefix( 'condition' ),
                        'selected' => $step->get_meta( 'condition' ),
                        'option_none' => false,
                        'attributes' => 'style="vertical-align:middle;"',
                        'options' =>
                            [
                                'any' => __( 'any' ),
                                'all' => __( 'all' ),
                            ]
                    ] ;

                    printf( __( 'Run when %s of these tags are removed:', 'groundhogg' ), WPGH()->html->dropdown( $args ) ); ?>
                </th>
                <?php $args = array(
                    'id' => $step->prefix( 'tags' ),
                    'name' => $step->prefix( 'tags' ) . '[]',
                    'selected' => $tags
                ); ?>
                <td>
                    <?php echo WPGH()->html->tag_picker( $args ); ?>
                    <p class="description"><?php _e( 'Add new tags by hitting [Enter] or by typing a [,].', 'groundhogg' ); ?></p>
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

        if ( isset( $_POST[ $step->prefix( 'condition' ) ] ) ){
            $step->update_meta( 'condition', $_POST[ $step->prefix( 'condition' ) ] === 'any' ? 'any' : 'all'  );
        }

    }

    /**
     * Whenever a tag is applied complete the following actions for the benchmark.
     *
     * @param $contact WPGH_Contact
     * @param $tag_id
     */
    public function complete( $contact, $tag_id )
    {
        /* just make sure */
        if ( $contact->has_tag( $tag_id ) )
            return;

        $steps = $this->get_like_steps();

        if ( empty( $steps ) )
            return;

        foreach ( $steps as $step ){

            $tags = $step->get_meta( 'tags' );
            $condition = $step->get_meta( 'condition' );

            switch ( $condition ){
                default:
                case 'any':
                    $not_has_tags = in_array( $tag_id, $tags );
                    break;
                case 'all':
                    $diff = array_diff( $tags, $contact->tags );
                    $not_has_tags = in_array( $tag_id, $tags ) && count( $diff ) === count( $tags );
                    break;
            }

            if ( $step->can_complete( $contact ) && $not_has_tags ){
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

    /**
     * @param array $args
     * @param WPGH_Step $step
     */
    public function import($args, $step)
    {
        if ( empty(  $args[ 'tags' ] ) )
            return;

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