<?php

use Groundhogg\Steps\Benchmarks\Benchmark;

/**
 * Minimal benchmark so the protected data_as_args() can be exercised directly
 */
class Data_As_Args_Benchmark extends Benchmark {

	public function get_name() {
		return 'Data as args';
	}

	public function get_type() {
		return 'data_as_args_test';
	}

	public function get_description() {
		return 'Test only';
	}

	public function settings( $step ) {
	}

	protected function get_complete_hooks() {
		return [];
	}

	protected function get_the_contact() {
		return false;
	}

	protected function can_complete_step() {
		return false;
	}

	/**
	 * Run data_as_args() over the given data
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public function args_for( array $data ) {
		$this->data = $data;

		return $this->data_as_args();
	}
}

/**
 * Benchmark::data_as_args() must turn stored objects into ids without ever reading a non-public property
 */
class Benchmark_Data_As_Args_Tests extends GH_UnitTestCase {

	/**
	 * @var Data_As_Args_Benchmark
	 */
	protected $benchmark;

	public function setUp(): void {
		parent::setUp();
		$this->benchmark = new Data_As_Args_Benchmark();
	}

	/**
	 * Like WC_Order: get_id() is the API, $id is protected, and reading it through __get is flagged as incorrect usage
	 *
	 * @return object
	 */
	protected function order_like( $id ) {
		return new class( $id ) {
			protected $id;

			public function __construct( $id ) {
				$this->id = $id;
			}

			public function get_id() {
				return $this->id;
			}

			public function __get( $key ) {
				_doing_it_wrong( $key, 'Properties should not be accessed directly', '1.0' );

				return $this->$key;
			}
		};
	}

	public function test_get_id_is_used_for_an_object_with_a_protected_id() {

		// No setExpectedIncorrectUsage(), so any _doing_it_wrong() from __get fails this test
		$args = $this->benchmark->args_for( [ 'order' => $this->order_like( 42 ) ] );

		$this->assertSame( [ 'order' => 42 ], $args );
	}

	public function test_get_id_wins_over_a_public_id_property() {

		$object = new class {
			public $ID = 1;
			public $id = 2;

			public function get_id() {
				return 3;
			}
		};

		$this->assertSame( [ 'thing' => 3 ], $this->benchmark->args_for( [ 'thing' => $object ] ) );
	}

	public function test_wp_post_uses_its_public_ID() {

		$post_id = self::factory()->post->create();

		$args = $this->benchmark->args_for( [ 'post' => get_post( $post_id ) ] );

		$this->assertSame( [ 'post' => $post_id ], $args );
	}

	public function test_a_call_catch_all_is_not_mistaken_for_get_id() {

		// Like WP_User::__call(), which answers false to every method name
		$object = new class {
			public $ID = 11;

			public function __call( $name, $arguments ) {
				return false;
			}
		};

		$this->assertSame( [ 'thing' => 11 ], $this->benchmark->args_for( [ 'thing' => $object ] ) );
	}

	public function test_wp_user_uses_its_public_ID() {

		$user_id = self::factory()->user->create();

		$args = $this->benchmark->args_for( [ 'user' => get_userdata( $user_id ) ] );

		$this->assertSame( [ 'user' => $user_id ], $args );
	}

	public function test_object_with_only_a_public_id_property() {

		$object = new class {
			public $id = 7;
		};

		$this->assertSame( [ 'thing' => 7 ], $this->benchmark->args_for( [ 'thing' => $object ] ) );
	}

	public function test_object_with_only_a_protected_id_and_no_magic_getter_is_left_out() {

		$object = new class {
			protected $id = 9;
		};

		// Expected: no exception, and the key is simply absent, since there is no public way to read the id
		$args = $this->benchmark->args_for( [ 'thing' => $object, 'other' => 5 ] );

		$this->assertSame( [ 'other' => 5 ], $args );
	}

	public function test_object_with_only_a_private_ID_is_left_out() {

		$object = new class {
			private $ID = 9;
		};

		$this->assertSame( [], $this->benchmark->args_for( [ 'thing' => $object ] ) );
	}

	public function test_scalars_pass_through_and_contacts_are_skipped() {

		$args = $this->benchmark->args_for( [
			'int'      => 5,
			'numeric'  => '6',
			'string'   => 'abc',
			'bool'     => true,
			'null'     => null,
			'contact'  => $this->order_like( 1 ),
			'contacts' => [ 1, 2 ],
		] );

		$this->assertSame( [ 'int' => 5, 'numeric' => '6', 'string' => 'abc' ], $args );
	}

	public function test_groundhogg_objects_use_their_id() {

		$contact_id = $this->factory()->contacts->create();
		$contact    = new \Groundhogg\Contact( $contact_id );

		$tag    = new \Groundhogg\Tag( [ 'tag_name' => 'Data as args' ] );
		$tag_id = $tag->get_id();

		$this->assertGreaterThan( 0, $tag_id );

		$args = $this->benchmark->args_for( [ 'thing' => $contact, 'tag' => $tag ] );

		$this->assertSame( [ 'thing' => $contact_id, 'tag' => $tag_id ], $args );
	}
}
