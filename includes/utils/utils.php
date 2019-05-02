<?php
namespace Groundhogg;

class Utils
{
    /**
     * @var Location
     */
    public $location;

    /**
     * @var HTML
     */
    public $html;

    /**
     * @var Files
     */
    public $files;

    /**
     * @var Date_Time
     */
    public $date_time;

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

        $this->location     = new Location();
        $this->html         = new HTML();
        $this->files        = new Files();
        $this->date_time    = new Date_Time();

    }

    /**
     * Get an object from the cache or return a new instance of the object.
     *
     * @param int $id
     * @param string|bool $by
     * @param string $object
     * @param bool $get_from_cache
     *
     * @return mixed|Base_Object
     */
    public function get_object( $id = 0 , $by = 'ID' , $object = 'contact', $get_from_cache = true ){

        $cache_key = md5(  $id . '|' . $by . '|' . $object );

        if ( $get_from_cache ){
            if (  key_exists( $cache_key, self::$object_cache ) ){
                return self::$object_cache[ $cache_key ];
            }
        }

        $class = gisset_not_empty( self::$class_object_map[ $object ] ) ? self::$class_object_map[ $object ] : ucfirst( $object );
        $class = apply_filters( 'groundhogg/utils/get_object', $class, $object );

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

    /**
     * @param $id
     * @param bool $get_from_cache
     * @return Step
     */
    public function get_step( $id, $get_from_cache=true )
    {
        return $this->get_object( $id, 'ID', 'step', $get_from_cache );
    }

    /**
     * @param $id
     * @param bool $get_from_cache
     * @return Event
     */
    public function get_event( $id, $get_from_cache=true )
    {
        return $this->get_object( $id, 'ID', 'event', $get_from_cache );
    }

    /**
     * @param $id
     * @param bool $get_from_cache
     * @return Funnel
     */
    public function get_funnel( $id, $get_from_cache=true )
    {
        return $this->get_object( $id, 'ID', 'funnel', $get_from_cache );
    }

    /**
     * @param $id
     * @param bool $get_from_cache
     * @return Email
     */
    public function get_email( $id, $get_from_cache=true )
    {
        return $this->get_object( $id, 'ID', 'email', $get_from_cache );
    }

    /**
     * @param $id
     * @param bool $get_from_cache
     * @return Sms
     */
    public function get_sms( $id, $get_from_cache=true )
    {
        return $this->get_object( $id, 'ID', 'sms', $get_from_cache );
    }


}