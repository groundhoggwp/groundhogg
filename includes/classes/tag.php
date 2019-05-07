<?php
namespace Groundhogg;


use Groundhogg\DB\DB;

/**
 * Created by PhpStorm.
 * User: atty
 * Date: 01-May-19
 * Time: 4:34 PM
 */

class Tag extends Base_Object {

    protected function post_setup()
    {
        // TODO: Implement post_setup() method.
    }

    protected function get_db()
    {
        return Plugin::$instance->dbs->get_db( 'tags' );
    }

    protected function get_relationships_db()
    {
        return Plugin::$instance->dbs->get_db( 'tag_relationships' );
    }

    protected function get_object_type()
    {
        return 'tag';
    }

    /**
     * @return string
     */
    public function get_name()
    {
        return $this->tag_name;
    }

    /**
     * @return int
     */
    public function get_id()
    {
        return absint( $this->tag_id );
    }

    /**
     * @return string
     */
    public function get_description()
    {
        return $this->tag_description;
    }

    /**
     * @return int
     */
    public function get_contact_count()
    {
        return absint( $this->contact_count );
    }

    /**
     * @return string
     */
    public function get_slug()
    {
        return $this->tag_slug;
    }

    /**
     * @return int[]
     */
    public function get_contact_ids()
    {
        $query = new Contact_Query();
        $contacts = $query->query( [ 'tags_include' => $this->get_id() ] );
        $contact_ids = wp_parse_id_list( wp_list_pluck( $contacts, 'ID') );
        return $contact_ids;
    }
}