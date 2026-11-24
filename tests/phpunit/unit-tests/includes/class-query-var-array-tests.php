<?php

use Groundhogg\Campaign;
use Groundhogg\Plugin;
use Groundhogg\Rewrites;
use function Groundhogg\get_db;

/**
 * Arrays from the URL must not reach the constructors of objects, which save an array they're given.
 *
 * ?campaign[ID]=1&campaign[name]=x renamed a campaign, and ?campaign[operation]=DELETE emptied the campaigns table,
 * without logging in.
 *
 * @see Rewrites::parse_query()
 * @see \Groundhogg\Base_Object::__construct()
 */
class Query_Var_Array_Tests extends GH_UnitTestCase {

	protected $run_id;

	public function setUp(): void {
		parent::setUp();
		$this->run_id = uniqid();
		wp_set_current_user( 0 );
	}

	protected function campaign( string $name = 'Original' ): Campaign {

		$id = get_db( 'campaigns' )->add( [
			'name'       => $name,
			'slug'       => sanitize_title( $name ) . '-' . $this->run_id,
			'visibility' => 'public',
		] );

		return new Campaign( $id );
	}

	protected function parse_query( array $query ): array {
		return Plugin::instance()->rewrites->parse_query( $query );
	}

	public function test_array_values_of_the_managed_page_vars_are_dropped() {

		$query = $this->parse_query( [
			'pagename'  => 'gh',
			'subpage'   => 'campaigns',
			'campaign'  => [ 'ID' => 1, 'name' => 'changed' ],
			'broadcast' => [ 'operation' => 'DELETE' ],
			'slug'      => [ 'operation' => 'DELETE' ],
		] );

		$this->assertSame( 'campaigns', $query['subpage'] );
		$this->assertArrayNotHasKey( 'campaign', $query );
		$this->assertArrayNotHasKey( 'broadcast', $query );
		$this->assertArrayNotHasKey( 'slug', $query );
	}

	public function test_single_values_and_other_vars_are_kept() {

		$query = $this->parse_query( [
			'subpage'   => 'campaigns',
			'campaign'  => 'my-campaign',
			'broadcast' => '12',
			'post_type' => [ 'post', 'page' ], // not ours, WordPress allows arrays for some of its own
		] );

		$this->assertSame( 'my-campaign', $query['campaign'] );
		$this->assertSame( '12', $query['broadcast'] );
		$this->assertSame( [ 'post', 'page' ], $query['post_type'] );
	}

	public function test_the_query_vars_are_still_registered() {

		$vars = apply_filters( 'query_vars', [] );

		foreach ( Rewrites::get_query_var_names() as $var ) {
			$this->assertContains( $var, $vars );
		}
	}

	public function test_a_delete_operation_passed_to_the_constructor_deletes_nothing() {

		$campaign = $this->campaign();
		$before   = get_db( 'campaigns' )->count();

		$built = new Campaign( [ 'operation' => 'DELETE' ] );

		$this->assertFalse( $built->exists() );
		$this->assertSame( $before, get_db( 'campaigns' )->count() );
		$this->assertTrue( ( new Campaign( $campaign->get_id() ) )->exists() );
	}

	public function test_an_update_operation_passed_to_the_constructor_changes_nothing() {

		$campaign = $this->campaign();

		new Campaign( [ 'operation' => 'UPDATE', 'data' => [ 'name' => 'changed' ] ] );

		$this->assertSame( 'Original', ( new Campaign( $campaign->get_id() ) )->get_name() );
	}

	public function test_an_array_without_columns_does_not_match_the_first_row() {

		$this->campaign();

		$built = new Campaign( [ 'orderby' => 'ID', 'order' => 'ASC' ] );

		$this->assertFalse( $built->exists() );
	}

	public function test_an_array_of_columns_still_finds_the_object() {

		$campaign = $this->campaign();

		$found = new Campaign( [ 'slug' => $campaign->get_slug() ] );

		$this->assertTrue( $found->exists() );
		$this->assertSame( $campaign->get_id(), $found->get_id() );
	}

	public function test_an_array_of_columns_still_creates_the_object_when_none_matches() {

		$name   = 'Created ' . $this->run_id;
		$before = get_db( 'campaigns' )->count();

		$created = new Campaign( [ 'name' => $name ] );

		$this->assertTrue( $created->exists() );
		$this->assertSame( $name, $created->get_name() );
		$this->assertSame( $before + 1, get_db( 'campaigns' )->count() );
	}

	public function test_an_array_with_the_id_still_updates_the_object() {

		$campaign = $this->campaign();

		new Campaign( [ 'ID' => $campaign->get_id(), 'name' => 'Renamed' ] );

		$this->assertSame( 'Renamed', ( new Campaign( $campaign->get_id() ) )->get_name() );
	}
}
