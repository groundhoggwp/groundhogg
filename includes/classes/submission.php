<?php
namespace Groundhogg;

use Groundhogg\DB\DB;
use Groundhogg\DB\Meta_DB;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-05-07
 * Time: 1:51 PM
 */

class Submission extends Base_Object_With_Meta
{

    /**
     * Return the DB instance that is associated with items of this type.
     *
     * @return DB
     */
    protected function get_db()
    {
        return Plugin::$instance->dbs->get_db( 'submissions' );
    }

    /**
     * Return a META DB instance associated with items of this type.
     *
     * @return Meta_DB
     */
    protected function get_meta_db()
    {
        return Plugin::$instance->dbs->get_db( 'submissionmeta' );
    }

    /**
     * Do any post setup actions.
     *
     * @return void
     */
    protected function post_setup()
    {
        // TODO: Implement post_setup() method.
    }

    /**
     * A string to represent the object type
     *
     * @return string
     */
    protected function get_object_type()
    {
        return 'submission';
    }

    public function get_step_id()
    {
        return absint( $this->step_id );
    }

    public function get_form_id()
    {
        return $this->get_step_id();
    }

    public function get_contact_id()
    {
        return absint( $this->contact_id );
    }

    public function get_contact()
    {
        return Plugin::$instance->utils->get_contact( $this->get_contact_id() );
    }
}