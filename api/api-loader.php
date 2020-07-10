<?php

namespace Groundhogg\Api;

use Groundhogg\Api\V3\API_V3;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-03-13
 * Time: 9:59 AM
 */
class Api_Loader {

	/**
	 * @var API_V3
	 */
	public $v3;

	/**
	 * WPGH_API_LOADER constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'load_api' ] );
	}

	public function load_api() {
		$this->v3 = new API_V3();
	}

}