<?php
namespace Groundhogg\Steps\Actions;

use Groundhogg\Contact;
use Groundhogg\Event;
use Groundhogg\HTML;
use Groundhogg\Plugin;
use Groundhogg\Step;

if ( ! defined( 'ABSPATH' ) ) exit;

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
class Remove_Tag extends Action
{

    /**
     * Get the element name
     *
     * @return string
     */
    public function get_name()
    {
        return _x( 'Remove Tag', 'step_name', 'groundhogg' );
    }

    /**
     * Get the element type
     *
     * @return string
     */
    public function get_type()
    {
        return 'remove_tag';
    }

    /**
     * Get the description
     *
     * @return string
     */
    public function get_description()
    {
        return _x( 'Remove a tag from a contact.', 'step_description', 'groundhogg' );
    }

    /**
     * Get the icon URL
     *
     * @return string
     */
    public function get_icon()
    {
        return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/remove-tag.png';
    }

    /**
     * @param $step Step
     */
    public function settings( $step )
    {

        $this->start_controls_section();

        $this->add_control( 'tags', [
            'label'         => __( 'Remove These Tags:', 'groundhogg' ),
            'type'          => HTML::TAG_PICKER,
            'description'   => __( 'Add new tags by hitting [enter] or by typing a [comma].', 'groundhogg' ),
            'field'         => [
                'multiple' => true,
            ]
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
        $this->save_setting( 'tags', Plugin::$instance->dbs->get_db( 'tags' )->validate( $this->get_posted_data( 'tags', [] ) ) );
    }

    /**
     * Process the apply tag step...
     *
     * @param $contact Contact
     * @param $event Event
     *
     * @return true
     */
    public function run( $contact, $event )
    {
        $tags = wp_parse_id_list( $this->get_setting( 'tags' ) );

        return $contact->add_tag( $tags );
    }

    /**
     * @param array $args
     * @param Step $step
     */
    public function import($args, $step)
    {
        if ( empty(  $args[ 'tags' ] ) )
            return;

        $tags = Plugin::$instance->dbs->get_db( 'tags' )->validate( $args[ 'tags' ] );

        $this->save_setting( 'tags', $tags );
    }

    /**
     * @param array $args
     * @param Step $step
     * @return array
     */
    public function export($args, $step)
    {
        $args['tags'] = array();

        $tags = wp_parse_id_list( $this->get_setting( 'tags' ) );

        if ( empty( $tags ) )
            return $args;

        foreach ( $tags as $tag_id ) {

            $tag = Plugin::$instance->dbs->get_db( 'tags' )->get( $tag_id );

            if ( $tag ){
                $args[ 'tags' ][] = $tag->tag_name;
            }

        }

        return $args;
    }
}