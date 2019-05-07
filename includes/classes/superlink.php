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

    protected function post_setup()
    {
        // TODO: Implement post_setup() method.
    }

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
        //todo
    }

    public function get_replacement_code()
    {
        //todo
    }

    /**
     * @return array
     */
    public function get_tags()
    {
        return $this->tags;
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