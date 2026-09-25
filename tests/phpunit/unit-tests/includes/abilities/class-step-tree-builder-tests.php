<?php

use Groundhogg\Abilities\Funnels\Step_Tree_Builder;
use Groundhogg\Funnel;
use Groundhogg\Step;

/**
 * Building steps from the tree of step nodes the flow abilities take
 */
class Step_Tree_Builder_Tests extends GH_UnitTestCase {

	protected function make_funnel( $status = 'inactive' ) {

		$this->factory()->truncate();

		return new Funnel( [
			'title'  => 'test funnel',
			'status' => $status
		] );
	}

	/**
	 * Build the nodes and finish the way the abilities do
	 */
	protected function build( Funnel $funnel, array $nodes, array $declared = [] ) {

		$builder = new Step_Tree_Builder( $funnel, $declared );
		$out     = $builder->build( $nodes );

		if ( ! is_wp_error( $out ) ) {
			$funnel->set_step_levels();
			$builder->apply_deferred_settings();
		}

		return $out;
	}

	protected function tasks_nodes( $tasks ) {
		return [
			[ 'type' => 'create_task', 'id' => 'call' ],
			[ 'type' => 'task_completed', 'id' => 'called', 'settings' => [ 'tasks' => $tasks ] ],
		];
	}

	public function test_builds_branches() {
		$funnel = $this->make_funnel();

		$out = $this->build( $funnel, [
			[ 'type' => 'delay_timer', 'title' => 'wait' ],
			[
				'type'     => 'if_else',
				'branches' => [
					'yes' => [ [ 'type' => 'delay_timer', 'title' => 'yes wait' ] ],
					'no'  => [ [ 'type' => 'delay_timer', 'title' => 'no wait' ] ],
				]
			],
		] );

		$this->assertNotWPError( $out );
		$this->assertCount( 2, $out );

		$if_else = $out[1];
		$yes     = new Step( $if_else['branches']['yes'][0]['id'] );
		$no      = new Step( $if_else['branches']['no'][0]['id'] );

		$this->assertEquals( "{$if_else['id']}-yes", $yes->branch );
		$this->assertEquals( "{$if_else['id']}-no", $no->branch );
		$this->assertEquals( 'main', ( new Step( $out[0]['id'] ) )->branch );

		// set_step_levels() puts the branch steps after the step they branch from
		$this->assertGreaterThan( ( new Step( $if_else['id'] ) )->get_order(), $yes->get_order() );

		$this->assertCount( 4, $funnel->get_steps() );
	}

	public function test_steps_are_inactive_on_an_active_funnel() {
		$funnel = $this->make_funnel( 'active' );

		$out = $this->build( $funnel, [ [ 'type' => 'delay_timer' ] ] );

		$this->assertEquals( 'inactive', ( new Step( $out[0]['id'] ) )->step_status );

		// not live until the funnel is committed
		$this->assertEmpty( $funnel->get_steps() );
	}

	public function test_references_an_earlier_step_after_the_order_is_final() {
		$funnel = $this->make_funnel();

		$out = $this->build( $funnel, $this->tasks_nodes( [ 'call' ] ) );

		$this->assertNotWPError( $out );
		$this->assertEquals( [ $out[0]['id'] ], wp_parse_id_list( ( new Step( $out[1]['id'] ) )->get_meta( 'tasks' ) ) );
	}

	public function test_deferred_settings_wait_for_apply_deferred_settings() {
		$funnel = $this->make_funnel();

		$builder = new Step_Tree_Builder( $funnel );
		$out     = $builder->build( $this->tasks_nodes( [ 'call' ] ) );

		$this->assertEmpty( ( new Step( $out[1]['id'] ) )->get_meta( 'tasks' ) );
	}

	public function test_references_a_declared_existing_step() {
		$funnel = $this->make_funnel();

		$out      = $this->build( $funnel, [ [ 'type' => 'create_task' ] ] );
		$existing = $out[0]['id'];

		$out = $this->build( $funnel, [
			[ 'type' => 'task_completed', 'settings' => [ 'tasks' => [ 'existing' ] ] ],
		], [ 'existing' => [ 'id' => $existing, 'type' => 'create_task' ] ] );

		$this->assertNotWPError( $out );
		$this->assertEquals( [ $existing ], wp_parse_id_list( ( new Step( $out[0]['id'] ) )->get_meta( 'tasks' ) ) );
	}

	public function test_get_declared_includes_built_steps() {
		$funnel  = $this->make_funnel();
		$builder = new Step_Tree_Builder( $funnel, [ 'existing' => [ 'id' => 1, 'type' => 'create_task' ] ] );

		$out = $builder->build( [ [ 'type' => 'delay_timer', 'id' => 'wait' ] ] );

		$this->assertEquals( [
			'existing' => [ 'id' => 1, 'type' => 'create_task' ],
			'wait'     => [ 'id' => $out[0]['id'], 'type' => 'delay_timer' ],
		], $builder->get_declared() );
	}

	public function test_errors() {
		$funnel = $this->make_funnel();

		$cases = [
			'groundhogg_unknown_step_reference' => [ $this->tasks_nodes( [ 'nope' ] ), [] ],
			'groundhogg_duplicate_step_id'      => [ [ [ 'type' => 'delay_timer', 'id' => 'taken' ] ], [ 'taken' => [ 'id' => 1, 'type' => 'delay_timer' ] ] ],
			'groundhogg_invalid_step_type'      => [ [ [ 'type' => 'not_a_step' ] ], [] ],
			'groundhogg_unexpected_branches'    => [ [ [ 'type' => 'delay_timer', 'branches' => [ 'yes' => [] ] ] ], [] ],
			'groundhogg_invalid_branch_key'     => [ [ [ 'type' => 'if_else', 'branches' => [ 'maybe' => [ [ 'type' => 'delay_timer' ] ] ] ] ], [] ],
		];

		foreach ( $cases as $code => [ $nodes, $declared ] ) {
			$out = ( new Step_Tree_Builder( $funnel, $declared ) )->build( $nodes );
			$this->assertWPError( $out, $code );
			$this->assertEquals( $code, $out->get_error_code() );
		}
	}

	public function test_create_flow_ability() {

		if ( ! function_exists( 'wp_get_ability' ) || ! wp_get_ability( 'groundhogg/create-flow' ) ) {
			$this->markTestSkipped( 'The groundhogg/create-flow ability is not registered.' );
		}

		$this->factory()->truncate();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$out = wp_get_ability( 'groundhogg/create-flow' )->execute( [
			'title' => 'built flow',
			'steps' => array_merge( $this->tasks_nodes( [ 'call' ] ), [
				[
					'type'     => 'if_else',
					'branches' => [ 'yes' => [ [ 'type' => 'delay_timer' ] ] ]
				]
			] ),
		] );

		$this->assertNotWPError( $out );
		$this->assertEquals( 'inactive', $out['status'] );

		$funnel = new Funnel( $out['id'] );
		$this->assertCount( 4, $funnel->get_steps() );
		$this->assertEquals( [ $out['steps'][0]['id'] ], wp_parse_id_list( ( new Step( $out['steps'][1]['id'] ) )->get_meta( 'tasks' ) ) );
		$this->assertEquals( "{$out['steps'][2]['id']}-yes", ( new Step( $out['steps'][2]['branches']['yes'][0]['id'] ) )->branch );
	}
}
