<?php
namespace Groundhogg;

use Groundhogg\DB\DB;
use Groundhogg\DB\Meta_DB;

abstract class Base_Object_With_Meta extends Base_Object
{

    /**
     * The meta data
     *
     * @var array
     */
    protected $meta = [];

    /**
     * @var DB
     */
    protected $meta_db;

    /**
     * Setup the class
     *
     * @param $object
     * @return bool
     */
    protected function setup_object($object)
    {

        $object = (object) $object;

        if (!is_object($object)) {
            return false;
        }

        $this->ID = absint( $object->ID );

        //Lets just make sure we all good here.
        $object = apply_filters( "groundhogg/{$this->get_object_type()}/setup", $object );

        // Setup the main data
        foreach ($object as $key => $value) {
            $this->$key = $value;
        }

        // Get all the meta data.
        $this->meta = $this->get_all_meta();

        $this->post_setup();

        return true;

    }

    /**
     * Get an object property
     *
     * @param $name
     * @return bool|mixed
     */
    public function __get($name)
    {
        // Check main data first
        if (property_exists($this, $name)) {
            return $this->$name;
        }

        // Check data array
        if (key_exists($name, $this->data)) {
            return $this->data[$name];
        }

        // Check meta data
        if (key_exists($name, $this->meta)) {
            return $this->meta[$name];
        }

        return false;
    }

    /**
     * Return a META DB instance associated with items of this type.
     *
     * @return Meta_DB
     */
    abstract protected function get_meta_db();

    /**
     * Sanitize columns when updating the object
     *
     * @param array $data
     * @return array
     */
    protected function sanitize_columns( $data=[] )
    {
        return $data;
    }

    /**
     * Get all the meta data.
     *
     * @return array
     */
    public function get_all_meta()
    {
        if ( ! empty( $this->meta ) ){
            return $this->meta;
        }

        $meta = $this->get_meta_db()->get_meta( $this->ID );

        foreach ( $meta as $meta_key => $array_values ){
            $this->meta[ $meta_key ] = maybe_unserialize( array_shift( $array_values ) );
        }

        return $this->meta;
    }

    /**
     * Get some meta
     *
     * @param $key
     * @return mixed
     */
    public function get_meta( $key )
    {

        if ( key_exists( $key, $this->meta ) ){
            return $this->meta[ $key ];
        }

        $val = $this->get_meta_db()->get_meta( $this->ID, $key, true );

        $this->meta[ $key ] = $val;

        return $val;
    }

    /**
     * Update some meta data
     *
     * @param $key
     * @param $value
     *
     * @return mixed
     */
    public function update_meta( $key, $value )
    {
        if ( $this->get_meta_db()->update_meta( $this->ID, $key, $value ) ){
            $this->meta[ $key ] = $value;

            return true;
        }

        return false;
    }

    /**
     * Add some meta
     *
     * @param $key
     * @param $value
     *
     * @return mixed
     */
    public function add_meta( $key, $value )
    {
        if ( $this->get_meta_db()->add_meta( $this->ID, $key, $value ) ){
            $this->meta[ $key ] = $value;

            return true;
        }

        return false;
    }


    /**
     * Delete some meta
     *
     * @param $key
     * @return mixed
     */
    public function delete_meta( $key )
    {
        unset( $this->meta[$key] );
        return $this->get_meta_db()->delete_meta( $this->ID, $key );
    }
}