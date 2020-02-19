<?php

use Groundhogg\Contact;
use Groundhogg\Funnel;
use Groundhogg\Plugin;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_db;

class GH_UnitTest_Factory_For_Funnel extends GH_UnitTest_Factory_For_Thing {

	/**
	 * GH_UnitTest_Factory_For_Funnel constructor.
	 *
	 * @param null $factory
	 */
	public function __construct( $factory = null ) {

		parent::__construct( $factory );

		$this->default_generation_definitions = array(
			'title'  => new WP_UnitTest_Generator_Sequence( 'Funnel %s' ),
			'status' => 'inactive'
		);
	}

	/**
	 * Pick a random
	 *
	 * @param $array
	 *
	 * @return mixed
	 */
	public function get_random_from_array( $array ) {
		if ( empty( $array ) ) {
			return false;
		}

		return $array[ mt_rand( 0, count( $array ) - 1 ) ];
	}

	/**
	 * Create a number of mock funnels that can be used to test benchmarks and actions at run time
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public function create_mock_funnels( $args = [] ) {

		/**
		 * @var $num             int the number of funnels to create
		 * @var $status          string the status of the funnel
		 * @var $actions         int the number of actions between benchmarks
		 * @var $benchmarks      int the number of benchmarks
		 * @var $benchmark_types string[] list of types allowed
		 * @var $action_types    string[] list of types allowed
		 */
		$args = wp_parse_args( $args, [
			'num'             => 0,
			'actions'         => 0,
			'benchmarks'      => 0,
			'benchmark_types' => Plugin::instance()->step_manager->get_benchmark_types(),
			'action_types'    => Plugin::instance()->step_manager->get_action_types(),
			'status'          => 'active'
		] );

		extract( $args, EXTR_OVERWRITE );

		$funnels = [];

		if ( ! $num ) {
			return $funnels;
		}

		// For the number of funnels requested
		for ( $i = 0; $i < $num; $i ++ ) {

			// Create a new funnel
			$funnel_id = $this->create( [
				'status' => $status
			] );

			// Establish the starting order
			$order = 1;

			for ( $y = 0; $y < $benchmarks; $y ++ ) {

				$type = $this->get_random_from_array( $benchmark_types );

				$this->factory->steps->create( [
					'funnel_id'  => $funnel_id,
					'step_type'  => $type,
					'step_group' => 'benchmark',
					'step_order' => $order,
				] );

				$order ++;

				for ( $z = 0; $z < $actions; $z ++ ) {

					$type = $this->get_random_from_array( $action_types );

					$this->factory->steps->create( [
						'funnel_id'  => $funnel_id,
						'step_type'  => $type,
						'step_group' => 'action',
						'step_order' => $order,
					] );

					$order ++;
				}
			}

			$funnels[] = $funnel_id;
		}

		if ( count( $funnels ) === 1 ) {
			return $funnels[0];
		}

		return $funnels;
	}

	/**
	 * Retrieves an object by ID.
	 *
	 * @param int $object_id The object ID.
	 *
	 * @return Funnel The object. Can be anything.
	 */
	public function get_object_by_id( $object_id ) {
		return new Funnel( $object_id );
	}

	/**
	 * Get the DB name
	 *
	 * @return string
	 */
	protected function get_db_name() {
		return 'funnels';
	}
}
