<?php

use Groundhogg\Contact;
use Groundhogg\Funnel;
use Groundhogg\Plugin;
use Groundhogg\Steps\Benchmarks\Tag_Applied;
use function Groundhogg\get_db;

/**
 * Benchmarks are singletons, so data and args set up for one hook fire must not leak into the next fire in the same request
 */
class Benchmark_State_Tests extends GH_UnitTestCase {

	public function test_each_fire_enqueues_its_own_args() {

		Plugin::instance()->dbs->truncate_dbs();

		$tags = [
			get_db( 'tags' )->add( [ 'tag_name' => 'Tag A' ] ),
			get_db( 'tags' )->add( [ 'tag_name' => 'Tag B' ] ),
			get_db( 'tags' )->add( [ 'tag_name' => 'Tag C' ] ),
		];

		$funnel = new Funnel( [
			'title'  => 'Test Funnel',
			'status' => 'active'
		] );

		$trigger = $funnel->add_step( [
			'step_title'  => 'Tag Applied',
			'step_type'   => Tag_Applied::TYPE,
			'step_group'  => Tag_Applied::GROUP,
			'step_status' => 'active',
		] );

		// the funnel is active so regular meta updates would be staged as changes
		$trigger->update_meta_bypass_changes( 'tags', $tags );
		$trigger->update_meta_bypass_changes( 'condition', 'any' );

		// Fire the same trigger several times in one request, like a bulk action would
		$expected = [];

		foreach ( $tags as $tag_id ) {
			$contact = new Contact( $this->factory()->contacts->create() );
			$contact->apply_tag( $tag_id );

			$expected[ $contact->get_id() ] = $tag_id;
		}

		$events = $trigger->get_waiting_events();

		$this->assertCount( 3, $events );

		foreach ( $events as $event ) {
			$this->assertEquals( $expected[ $event->get_contact_id() ], $event->args['tag_id'] );
		}
	}

}
