<?php
namespace Groundhogg;

use Groundhogg\DB\DB;

class Funnel extends Base_Object
{

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
     * Return the DB instance that is associated with items of this type.
     *
     * @return DB
     */
    protected function get_db()
    {
        return Plugin::instance()->dbs->get_db( 'funnels' );
    }

    /**
     * A string to represent the object type
     *
     * @return string
     */
    protected function get_object_type()
    {
        return 'funnel';
    }
}