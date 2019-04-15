<?php
namespace Groundhogg;

class Utils
{
    /**
     * @var Locations
     */
    public $locations;

    /**
     * @var HTML
     */
    public $html;

    /**
     * @var Base_Object[]
     */
    protected static $object_cache = [];

    /**
     * Map for type to Class Name
     *
     * @var string[]
     */
    protected static $class_object_map = [
        'contact'   => 'Contact',
        'funnel'    => 'Funnel',
        'step'      => 'Step',
        'event'     => 'Event',
        'email'     => 'Email',
        'sms'       => 'Sms',
        'broadcast' => 'Broadcast',
        'superlink' => 'Superlink',
        'tag'       => 'Tag',
    ];

    /**
     * Utils constructor.
     */
    public function __construct()
    {

        $this->locations    = new Locations();
        $this->html         = new HTML();

    }

    /**
     * Get an object from the cache or rtuent a new instance of the object.
     *
     * @param int $id
     * @param string|bool $by
     * @param string $type
     * @param bool $get_from_cache
     *
     * @return mixed|Base_Object
     */
    public function get_object( $id = 0 , $by = 'ID' , $type = 'contact', $get_from_cache = true ){

        $cache_key = md5(  $id . '|' . $by . '|' . $type );

        if ( $get_from_cache ){
            if (  key_exists( $cache_key, self::$object_cache ) ){
                return self::$object_cache[ $cache_key ];
            }
        }

        $class = gisset_not_empty( self::$class_object_map[ $type ] ) ? self::$class_object_map[ $type ] : ucfirst( $type );
        $class = apply_filters( 'groundhogg/utils/get_object', $class, $type );

        if ( ! $class ){
            return false;
        }

        /**
         * @type $object Base_Object
         */
        $object = new $class( $id, $by );

        if ( $object && $object->exists() ){
            self::$object_cache[ $cache_key ] = $object;
        }

        return false;
    }

    /**
     * Get a contact!
     *
     * @param $id_or_email
     * @param bool $by_user_id
     * @param bool $get_from_cache
     * @return Contact
     */
    public function get_contact( $id_or_email, $by_user_id=false, $get_from_cache=true )
    {
        return $this->get_object( $id_or_email, $by_user_id, 'contact', $get_from_cache );
    }



}