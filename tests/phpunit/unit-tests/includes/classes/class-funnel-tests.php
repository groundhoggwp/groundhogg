<?php

use Groundhogg\Funnel;
use Groundhogg\Plugin;
use Groundhogg\Step;
use Groundhogg\Steps\Actions\Send_Email;
use Groundhogg\Steps\Benchmarks\Account_Created;
use Groundhogg\Steps\Benchmarks\Tag_Applied;
use function Groundhogg\get_db;

class Funnel_Tests extends GH_UnitTestCase {

	/**
	 * Check to ensure the new add_step function works right
	 *
	 * @return void
	 */
	public function test_add_step_function() {

		Plugin::$instance->dbs->truncate_dbs();

		$funnel = new Funnel( [
			'title' => 'test funnel'
		] );

		$step = $funnel->add_step( [
			'step_type'  => Account_Created::TYPE,
			'step_group' => Account_Created::GROUP,
			'step_title' => 'test step'
		] );

		$this->assertNotEmpty( $funnel->get_step_ids() );
		$this->assertEquals( 1, $step->get_order() );

		$step = $funnel->add_step( [
			'step_type'  => Send_Email::TYPE,
			'step_group' => Send_Email::GROUP,
			'step_title' => 'test step'
		] );

		$this->assertEquals( 2, $step->get_order() );
		$this->assertEquals( 2, count( $funnel->get_step_ids() ) );

	}

//	public function test_contact_cant_move_backwards() {
//
//		Plugin::$instance->dbs->truncate_dbs();
//
//		$tags = [
//			'Start',
//			'Stop',
//			'Add',
//			'Remove'
//		];
//
//		$tags = get_db( 'tags' )->validate( $tags );
//
//		$funnel = new Funnel( [
//			'title' => 'test funnel'
//		] );
//
//		// Tag Applied at start of funnel
//		$start = $funnel->add_step( [
//			'step_type'  => Tag_Applied::TYPE,
//			'step_group' => Step::BENCHMARK,
//			'step_title' => 'tag-applied',
//			'meta' => [
//				'tags' => $tags[ 0 ],
//			]
//		] );
//
//	}


}
