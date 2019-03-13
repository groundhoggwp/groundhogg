<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-03-13
 * Time: 9:59 AM
 */

class WPGH_API_LOADER
{

    /**
     * @var WPGH_API_V2
     */
    public $v2;

    /**
     * @var WPGH_API_V3
     */
    public $v3;

    /**
     * WPGH_API_LOADER constructor.
     */
    public function __construct()
    {
        add_action( 'rest_api_init', array( $this, 'load_api' ) );
    }

    public function load_api()
    {

        $this->include_api_files();

        $this->v2 = new WPGH_API_V2();
        $this->v3 = new WPGH_API_V3();
    }

    public function include_api_files()
    {
        include dirname( __FILE__ ) . '/v2/class-wpgh-api-v2.php';
        include dirname( __FILE__ ) . '/v3/class-wpgh-api-v3.php';
    }

}