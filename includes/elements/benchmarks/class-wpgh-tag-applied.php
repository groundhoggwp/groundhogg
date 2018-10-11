<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-10-04
 * Time: 5:42 PM
 */

class WPGH_Tag_Applied extends WPGH_Funnel_Step
{

    /**
     * @var string
     */
    public $type    = 'tag_applied';

    /**
     * @var string
     */
    public $group   = 'benchmark';

    /**
     * @var string
     */
    public $icon    = 'tag-applied.png';

    /**
     * @var string
     */
    public $name    = 'Tag Applied';

    /**
     * Add the completion action
     *
     * WPGH_Tag_Applied constructor.
     */
    public function __construct()
    {
        parent::__construct();

        add_action( 'wpgh_tag_applied', array( $this, 'complete' ), 10, 2 );
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
                    <?php echo esc_html__( 'Run when any of these tags are applied:', 'groundhogg' ); ?>
                </th>
                <?php $args = array(
                    'id' => $step->prefix( 'tags' ),
                    'name' => $step->prefix( 'tags' ),
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
     * Whenever a tag is applied complete the following actions for the benchmark.
     *
     * @param $contact WPGH_Contact
     * @param $tag_id
     */
    public function complete( $contact, $tag_id )
    {
        /* just make sure */
        if ( ! $contact->has_tag( $tag_id ) )
            return;


        $steps = WPGH()->steps->get_steps( array( 'type' => $this->type, 'group' => $this->group ) );

        if ( empty( $steps ) )
            return;

        foreach ( $steps as $step ){

            $step = new WPGH_Step( $step->ID );

            $tags = $step->get_meta( 'tags' );

            if ( ! is_array( $tags ) )
                $tags = array();

            if ( $step->can_complete( $contact ) && in_array( $tag_id, $tags ) ){

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