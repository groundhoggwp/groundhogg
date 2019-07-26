<?php
namespace Groundhogg;

use Groundhogg\DB\DB;
use Serializable;
use ArrayAccess;

abstract class Base_Object extends Supports_Errors implements Serializable, ArrayAccess
{

    /**
     * The ID of the object
     *
     * @var int
     */
    public $ID = 0;

    /**
     * The regular data
     *
     * @var array
     */
    protected $data = [];

    /**
     * @var DB
     */
    protected $db;

    /**
     * Base_Object constructor.
     * @param $identifier_or_args int|string the identifier to look for
     * @param $field string the file to query
     */
    public function __construct( $identifier_or_args = 0, $field = null )
    {
        if ( ! $field ){
            $field = $this->get_identifier_key();
        }

        // Assume we are creating an object...
        if ( is_array( $identifier_or_args ) ){

            $query = $this->get_db()->query( $identifier_or_args );

            $object = array_shift( $query );

            if ( $object && ! empty( $object ) ){

                $this->setup_object( $object );

            } else {

                $id = $this->create( $identifier_or_args );

            }

        } else {

            $object = $this->get_from_db( $field, $identifier_or_args );

            if ( ! $object || empty( $object ) )
                return false;

            $this->setup_object( $object );
        }
    }

    /**
     * @return int
     */
    public function get_id()
    {
        return absint( $this->ID );
    }

    /**
     * @param $id
     */
    protected function set_id( $id )
    {
        $this->ID = absint( $id );
    }

    /**
     * @return string
     */
    protected function get_identifier_key()
    {
        return $this->get_db()->get_primary_key();
    }

    /**
     * Delete the object from the DB
     *
     * @return bool
     */
    public function delete()
    {
        if ( $this->get_db()->delete( $this->get_id() ) ){
            unset( $this->data );
            unset( $this->ID );

            return true;
        }

        return false;
    }

    /**
     * Setup the class
     *
     * @param $object
     * @return bool
     */
    protected function setup_object($object)
    {
        $object = (object) $object;

        if ( ! is_object( $object ) ) {
            return false;
        }

        $identifier = $this->get_identifier_key();

        $this->set_id( absint( $object->$identifier ) );

        //Lets just make sure we all good here.
        $object = apply_filters( "groundhogg/{$this->get_object_type()}/setup", $object );

        // Setup the main data
        foreach ($object as $key => $value) {
            $this->$key = $value;
        }

        $this->data[ 'ID' ] = $this->get_id();

        $this->post_setup();

        return true;

    }

    /**
     * Do any post setup actions.
     *
     * @return void
     */
    abstract protected function post_setup();

    /**
     * Set an object property.
     *
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        if (property_exists($this, $name)) {
            $this->$name = $value;
        }

        $this->data[$name] = $value;
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

        if ( method_exists( $this, $name ) && is_callable( [ $this, $name ] ) ){
            return call_user_func( [ $this, $name ] );
        }

        return false;
    }

    /**
     * is triggered by calling isset() or empty() on inaccessible members.
     *
     * @param $name string
     * @return bool
     * @link https://php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.members
     */
    public function __isset($name)
    {
        return isset( $this->$name );
    }

    /**
     * Get the original data
     *
     * @return array
     */
    public function get_data()
    {
        return $this->data;
    }

    /**
     * Checks if the data from the DB checks out.
     *
     * @return bool
     */
    public function exists()
    {
        $has_data = false;

        foreach ( $this->data as $key => $datum ){
            if ( ! empty( $datum ) ){
                $has_data = true;
            }
        }

        return $has_data;
    }

    /**
     * Get the object from the associated db.
     * @param $field
     * @param $search
     * @return object
     */
    protected function get_from_db( $field='', $search='' )
    {
        return $this->get_db()->get_by( $field, $search );
    }

    /**
     * Return the DB instance that is associated with items of this type.
     *
     * @return DB
     */
    abstract protected function get_db();

    /**
     * A string to represent the object type
     *
     * @return string
     */
    protected function get_object_type(){
        return $this->get_db()->get_object_type();
    }

    /**
     * Update the object
     *
     * @param array $data
     * @return bool
     */
    public function update( $data = [] )
    {
        if ( empty( $data ) ) {
            return false;
        }

        $data = $this->sanitize_columns( $data );

        do_action( "groundhogg/{$this->get_object_type()}/pre_update", $this->get_id(), $data, $this );

        if ( $updated = $this->get_db()->update( $this->get_id(), $data, $this->get_identifier_key() ) ) {

            $object = $this->get_from_db( $this->get_identifier_key(), $this->get_id() );
            $this->setup_object( $object );
            do_action( "groundhogg/{$this->get_object_type()}/post_update", $this->get_id(), $data, $this );

        }

        return $updated;
    }

    /**
     * Update the object
     *
     * @param array $data
     * @return bool
     */
    public function create( $data = [] )
    {
        if ( empty( $data ) ) {
            return false;
        }

        $data = $this->sanitize_columns( $data );

        do_action( "groundhogg/{$this->get_object_type()}/pre_create", $data, $this );

        if ( $id = $this->get_db()->add( $data ) ) {

            $object = $this->get_from_db( $this->get_identifier_key(), $id );

            if ( ! $object ){
                return false;
            }

            $this->setup_object( $object );

            do_action( "groundhogg/{$this->get_object_type()}/post_create", $this->get_id(), $data, $this );

        }

        return $id;
    }

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
     * String representation of object
     * @link https://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     * @since 5.1.0
     */
    public function serialize()
    {
        return serialize( $this->get_as_array() );
    }

    /**
     * @param string $serialized
     */
    public function unserialize( $serialized )
    {
        /**
         * @var $data array
         */
        $data = unserialize( $serialized );
        $data = $data[ 'data' ];

        $this->setup_object( $data );
    }

    /**
     * Whether a offset exists
     * @link https://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        if ( property_exists( $this, $offset ) ){
            return $this->$offset !== null;
        }

        return isset($this->data[$offset]);
    }

    /**
     * Offset to retrieve
     * @link https://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        if ( property_exists( $this, $offset ) ){
            return $this->$offset;
        }

        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }

    /**
     * Offset to set
     * @link https://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    /**
     * Offset to unset
     * @link https://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        if ( property_exists( $this, $offset ) ){
            $this->$offset = null;
        }

        unset($this->data[$offset]);
    }

    /**
     * @return array
     */
    public function get_as_array()
    {
        return apply_filters( "groundhogg/{$this->get_object_type()}/get_as_array", [ 'data' => $this->data ] );
    }
}