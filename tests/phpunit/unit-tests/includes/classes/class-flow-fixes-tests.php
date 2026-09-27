<?php

use Groundhogg\Abilities\Funnels\Step_Tree_Builder;
use Groundhogg\Funnel;
use Groundhogg\Step;
use function Groundhogg\event_queue_db;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_db;

/**
 * Committing, importing, enqueueing at logic steps, new step settings, and step warnings
 */
class Flow_Fixes_Tests extends GH_UnitTestCase {

	public function setUp(): void {
		parent::setUp();

		$this->factory()->truncate();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	/**
	 * An active flow with two delays, published
	 *
	 * @return array [ Funnel, Step[] ]
	 */
	protected function active_flow() {

		$funnel = new Funnel( [ 'title' => 'fixes', 'status' => 'active' ] );

		$steps = [];

		foreach ( [ 'first', 'second' ] as $title ) {
			$steps[] = $funnel->add_step( [
				'step_type'   => 'delay_timer',
				'step_group'  => Step::ACTION,
				'step_title'  => $title,
				'step_status' => 'active',
			] );
		}

		$funnel->commit();

		return [ $funnel, $steps ];
	}

	public function test_commit_says_whether_it_committed() {
		[ $funnel ] = $this->active_flow();

		$this->assertTrue( $funnel->commit() );

		$funnel->update( [ 'status' => 'inactive' ] );

		$this->assertFalse( $funnel->commit() );
	}

	public function test_the_rest_commit_route_succeeds() {
		[ $funnel ] = $this->active_flow();

		$request = new WP_REST_Request( 'POST', "/gh/v4/funnels/{$funnel->get_id()}/commit" );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( [ 'description' => 'committed' ] ) );

		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_deleted_steps_are_changes() {
		[ $funnel, $steps ] = $this->active_flow();

		$funnel->while_editing( function () use ( $funnel ) {
			$this->assertFalse( $funnel->has_changes() );
		} );

		$steps[1]->delete();

		$funnel->while_editing( function () use ( $funnel ) {
			$this->assertTrue( $funnel->has_changes() );
		} );
	}

	public function test_import_passes_the_import_data_on() {
		[ $funnel ] = $this->active_flow();

		$export = json_decode( wp_json_encode( $funnel->export() ), true );

		$after = null;
		$hook  = function ( $imported, $data ) use ( &$after ) {
			$after = $data;
		};

		add_action( 'groundhogg/funnel/import/after', $hook, 10, 2 );
		( new Funnel() )->import( $export );
		remove_action( 'groundhogg/funnel/import/after', $hook );

		$this->assertIsArray( $after );
		$this->assertCount( 2, $after['steps'] );
	}

	/**
	 * The imported_step_id rows in the table, not the meta cache
	 */
	protected function imported_step_id_rows( int $step_id ) {
		global $wpdb;

		$db = get_db( 'stepmeta' );

		return $wpdb->get_col( $wpdb->prepare(
			"SELECT meta_value FROM {$db->table_name} WHERE {$db->get_object_id_col()} = %d AND meta_key = 'imported_step_id'",
			$step_id
		) );
	}

	public function test_import_only_cleans_up_its_own_steps() {
		[ $funnel, $steps ] = $this->active_flow();

		// another import that's still running
		get_db( 'stepmeta' )->add_meta( $steps[0]->get_id(), 'imported_step_id', 999 );

		$export = json_decode( wp_json_encode( $funnel->export() ), true );
		$id     = ( new Funnel() )->import( $export );

		$this->assertEquals( [ '999' ], $this->imported_step_id_rows( $steps[0]->get_id() ) );

		foreach ( ( new Funnel( $id ) )->get_real_steps() as $step ) {
			$this->assertEmpty( $this->imported_step_id_rows( $step->get_id() ) );
		}
	}

	public function test_import_mode_ends_with_the_import() {
		[ $funnel ] = $this->active_flow();

		$this->assertFalse( Step::is_importing() );

		$during = null;
		$hook   = function ( $meta_id, $object_id, $meta_key ) use ( &$during ) {
			if ( $meta_key === 'imported_step_id' ) {
				$during = Step::is_importing();
			}
		};

		add_action( 'added_step_meta', $hook, 10, 3 );
		( new Funnel() )->import( json_decode( wp_json_encode( $funnel->export() ), true ) );
		remove_action( 'added_step_meta', $hook );

		$this->assertTrue( $during );
		$this->assertFalse( Step::is_importing() );
	}

	public function test_enqueueing_at_a_logic_step_with_nothing_after_it() {
		$funnel = new Funnel( [ 'title' => 'empty branches', 'status' => 'active' ] );

		$if_else = $funnel->add_step( [
			'step_type'   => 'if_else',
			'step_group'  => Step::LOGIC,
			'step_status' => 'active',
		] );

		$contact = get_contactdata( $this->factory()->contacts->create() );

		$this->assertFalse( ( new Step( $if_else->get_id() ) )->enqueue( $contact ) );
		$this->assertEquals( 0, event_queue_db()->count( [ 'contact_id' => $contact->get_id() ] ) );
	}

	public function test_built_steps_get_initial_settings() {
		$funnel = new Funnel( [ 'title' => 'initial', 'status' => 'inactive' ] );

		$initial = Step_Tree_Builder::get_initial_settings( 'web_form' );
		$this->assertNotEmpty( $initial );

		$builder = new Step_Tree_Builder( $funnel );
		$out     = $builder->build( [ [ 'type' => 'web_form', 'settings' => [ 'form_name' => 'My form' ] ] ] );

		$this->assertNotWPError( $out );

		$step = new Step( $out[0]['id'] );

		// given settings win over initial ones
		$this->assertEquals( 'My form', $step->get_meta( 'form_name' ) );

		foreach ( array_diff_key( $initial, [ 'form_name' => true ] ) as $key => $value ) {
			$this->assertEquals( $value, $step->get_meta( $key ), $key );
		}
	}

	public function test_warnings_have_their_error_code() {
		$funnel = new Funnel( [ 'title' => 'warnings', 'status' => 'inactive' ] );

		$step = $funnel->add_step( [
			'step_type'  => 'apply_tag',
			'step_group' => Step::ACTION,
		] );

		$step->get_step_element()->validate_settings( $step );

		$this->assertNotEmpty( $step->get_errors() );

		$html = $step->html_v2( false );

		$this->assertStringContainsString( 'data-error-code="' . $step->get_errors()[0]->get_error_code() . '"', $html );
	}
}
