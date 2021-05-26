<?php

namespace Groundhogg;

use Groundhogg\DB\DB;
use Groundhogg\DB\Event_Queue;
use Groundhogg\DB\Events;
use Groundhogg\DB\Meta_DB;
use Groundhogg\DB\Step_Meta;
use Groundhogg\DB\Steps;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Temp does not require DB connection
 *
 * Class Temp_Step
 *
 * @package Groundhogg
 */
class Temp_Step extends Step {

	public function __construct( $id, $data, $meta ) {
		$this->set_id( $id );
		$this->data = $data;
		$this->meta = $meta;

		$this->post_setup();
	}

}