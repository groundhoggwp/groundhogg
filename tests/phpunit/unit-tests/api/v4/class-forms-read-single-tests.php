<?php

use function Groundhogg\get_db;

/**
 * GET gh/v4/forms/{id}. The route was registered with no handler, so asking for one form by id had nothing to call.
 * It reads the same item the list does, and only what the list would: a form step that is active.
 */
class Forms_Read_Single_Tests extends GH_UnitTestCase {

	public function tearDown(): void {
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	protected function user( string $role ): int {
		return self::factory()->user->create( [ 'role' => $role ] );
	}

	/**
	 * @param string $type   step type
	 * @param string $status step status
	 * @param string $funnel funnel status
	 *
	 * @return int the step id
	 */
	protected function step( string $type = 'web_form', string $status = 'active', string $funnel = 'active' ): int {

		$funnel_id = self::factory()->funnels->create( [ 'status' => $funnel ] );

		return (int) get_db( 'steps' )->add( [
			'funnel_id'   => $funnel_id,
			'step_title'  => 'Site visit report',
			'step_status' => $status,
			'step_type'   => $type,
			'step_group'  => 'benchmark',
			'step_order'  => 1,
		] );
	}

	protected function get( int $id, array $params = [] ) {
		$request = new WP_REST_Request( 'GET', "/gh/v4/forms/$id" );
		$request->set_query_params( $params );

		return rest_do_request( $request );
	}

	public function test_a_form_can_be_read_by_its_id() {
		$id = $this->step();

		wp_set_current_user( $this->user( 'administrator' ) );

		$response = $this->get( $id );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $id, (int) $data['item']->get_id() );
		$this->assertSame( 'success', $data['status'] );

		// the same shape the list gives, which is what renders a form
		$array = json_decode( wp_json_encode( $data['item'] ), true );

		$this->assertSame( $id, $array['ID'] );
		$this->assertArrayHasKey( 'name', $array );
		$this->assertIsString( $array['rendered'] );
		$this->assertArrayHasKey( 'settings', $array );
	}

	public function test_a_rep_can_read_a_form_because_they_can_fill_one_out() {
		$id = $this->step();

		wp_set_current_user( $this->user( 'sales_rep' ) );

		$this->assertSame( 200, $this->get( $id )->get_status() );
	}

	public function test_someone_who_cannot_add_contacts_cannot_read_a_form() {
		$id = $this->step();

		wp_set_current_user( $this->user( 'subscriber' ) );

		$this->assertContains( $this->get( $id )->get_status(), [ 401, 403 ] );

		wp_set_current_user( 0 );

		$this->assertContains( $this->get( $id )->get_status(), [ 401, 403 ] );
	}

	public function test_a_form_that_does_not_exist_is_a_404() {
		wp_set_current_user( $this->user( 'administrator' ) );

		$this->assertSame( 404, $this->get( 99999999 )->get_status() );
	}

	/**
	 * Otherwise this would read any step of any flow by guessing ids.
	 */
	public function test_a_step_that_is_not_a_form_is_a_404() {
		$id = $this->step( 'send_email' );

		wp_set_current_user( $this->user( 'administrator' ) );

		$this->assertSame( 404, $this->get( $id )->get_status() );
	}

	public function test_a_form_that_is_not_active_is_a_404() {
		$id = $this->step( 'web_form', 'ready' );

		wp_set_current_user( $this->user( 'administrator' ) );

		$this->assertSame( 404, $this->get( $id )->get_status() );
	}

	/**
	 * What the list calls active is the step's own status, so the `active` flag the list takes changes nothing here.
	 */
	public function test_the_active_flag_the_list_takes_is_accepted() {
		$id = $this->step();

		wp_set_current_user( $this->user( 'administrator' ) );

		$this->assertSame( 200, $this->get( $id, [ 'active' => 1 ] )->get_status() );
	}
}
