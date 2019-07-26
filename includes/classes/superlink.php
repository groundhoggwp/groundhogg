<?php
namespace Groundhogg;

use Groundhogg\DB\DB;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Superlink
 *
 * Process a superlink if one is in progress.
 *
 * @package     Includes
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.9
 */
class Superlink extends Base_Object
{

    protected function get_db()
    {
        return Plugin::$instance->dbs->get_db( 'superlinks' );
    }

    protected function get_object_type()
    {
        return 'superlink';
    }

    public function get_name()
    {
        return $this->name;
    }

    public function get_source_url()
    {
        return trailingslashit( sprintf( site_url( 'gh/superlinks/link/%d' ), $this->get_id() ) );
    }

    public function get_replacement_code()
    {
        return sprintf( '{superlink.%d}', $this->get_id() );
    }

    protected function post_setup()
    {
        // TODO: Implement post_setup() method.
    }

    /**
     * @param $contact Contact
     */
    public function process( $contact )
    {
        if ( ! $contact ){
            return;
        }

        $contact->apply_tag( $this->get_tags() );
        $target = Plugin::$instance->replacements->process( $this->get_target_url(), $contact->get_id() );
        die( wp_redirect( $target ) );
    }

    /**
     * @return array
     */
    public function get_tags()
    {
        return wp_parse_id_list( $this->tags );
    }

    /**
     * @return string
     */
    public function get_target_url()
    {
        return $this->target;

    }

    /**
     * @return int
     */
    public function get_id()
    {
        return absint( $this->ID );
    }

}