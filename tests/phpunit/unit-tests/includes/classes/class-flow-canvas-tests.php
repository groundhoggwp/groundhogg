<?php

use Groundhogg\Abilities\Funnels\Flow_Changes;
use Groundhogg\Funnel;
use Groundhogg\Plugin;
use Groundhogg\Step;
use Groundhogg\Steps\Actions\Action;
use function Groundhogg\get_db;
use function Groundhogg\set_lock;

/**
 * The flow editor draws the canvas in JS from Funnel::get_canvas_data(), see assets/js/admin/funnels/flow-canvas.js
 *
 * The flows here are written out as fixtures for tests/js/flow-canvas.test.js, which checks that the JS draws the same
 * markup the server's sortable_item() does. Set GH_UPDATE_JS_FIXTURES=1 to write them, otherwise the server's markup is
 * checked against the fixtures, so a change to it fails here until they're updated.
 */
class Flow_Canvas_Tests extends GH_UnitTestCase {

	/**
	 * tests/js/fixtures/canvas
	 */
	protected function fixtures_dir() {
		return dirname( __DIR__, 4 ) . '/js/fixtures/canvas/';
	}

	protected $order = 0;

	public function setUp(): void {

		// the fixtures have step IDs in them, so start them from 1
		// this commits, so it has to happen before the test's transaction starts
		global $wpdb;
		$steps = get_db( 'steps' );
		$wpdb->query( "DELETE FROM {$steps->table_name}" );
		$wpdb->query( "ALTER TABLE {$steps->table_name} AUTO_INCREMENT = 1" );

		parent::setUp();

		$this->factory()->truncate();
		$this->order = 0;

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	/**
	 * Add a step after the ones added before it
	 */
	protected function add( Funnel $funnel, string $type, string $group, string $branch = 'main', array $data = [], array $meta = [] ): Step {
		return $funnel->add_step( array_merge( [
			'step_type'   => $type,
			'step_group'  => $group,
			'step_title'  => $type,
			'branch'      => $branch,
			'step_order'  => ++ $this->order,
			'step_status' => $funnel->is_active() ? 'active' : 'inactive',
			'meta'        => $meta,
		], $data ) );
	}

	protected function simple_flow() {
		$funnel = new Funnel( [ 'title' => 'simple', 'status' => 'inactive' ] );

		$this->add( $funnel, 'web_form', Step::BENCHMARK, 'main', [], [ 'form_name' => 'Sign up' ] );
		$this->add( $funnel, 'send_email', Step::ACTION ); // no email, has warnings
		$this->add( $funnel, 'delay_timer', Step::ACTION, 'main', [ 'is_locked' => 1 ], [ 'step_notes' => 'Wait **a bit**' ] );
		$this->add( $funnel, 'apply_tag', Step::ACTION );
		$this->add( $funnel, 'not_a_real_type', Step::ACTION ); // unregistered

		$funnel->set_step_levels();

		return $funnel;
	}

	protected function benchmarks_flow() {
		$funnel = new Funnel( [ 'title' => 'benchmarks', 'status' => 'inactive' ] );

		$applied = $this->add( $funnel, 'tag_applied', Step::BENCHMARK );
		$this->add( $funnel, 'web_form', Step::BENCHMARK );
		$this->add( $funnel, 'delay_timer', Step::ACTION, "$applied->ID" );
		$this->add( $funnel, 'apply_tag', Step::ACTION, "$applied->ID" );
		$this->add( $funnel, 'apply_note', Step::ACTION );
		$removed = $this->add( $funnel, 'tag_removed', Step::BENCHMARK, 'main', [ 'can_passthru' => 1, 'is_entry' => 1 ] );
		$this->add( $funnel, 'link_click', Step::BENCHMARK, 'main', [ 'is_conversion' => 1 ] );
		$this->add( $funnel, 'remove_tag', Step::ACTION, "$removed->ID" );
		$this->add( $funnel, 'account_created', Step::BENCHMARK );
		$this->add( $funnel, 'create_task', Step::ACTION );

		$funnel->set_step_levels();

		return $funnel;
	}

	protected function branches_flow() {
		$funnel = new Funnel( [ 'title' => 'branches', 'status' => 'inactive' ] );

		$this->add( $funnel, 'tag_applied', Step::BENCHMARK );
		$if_else = $this->add( $funnel, 'if_else', Step::LOGIC );
		$this->add( $funnel, 'delay_timer', Step::ACTION, "$if_else->ID-yes" );
		$nested = $this->add( $funnel, 'if_else', Step::LOGIC, "$if_else->ID-yes" );
		$this->add( $funnel, 'apply_tag', Step::ACTION, "$nested->ID-yes" );
		$trigger = $this->add( $funnel, 'tag_removed', Step::BENCHMARK, "$if_else->ID-no" );
		$this->add( $funnel, 'apply_note', Step::ACTION, "$trigger->ID" );
		$this->add( $funnel, 'logic_stop', Step::LOGIC, "$if_else->ID-no" );
		$this->add( $funnel, 'logic_jump', Step::LOGIC );
		$this->add( $funnel, 'send_email', Step::ACTION );

		$funnel->set_step_levels();

		return $funnel;
	}

	/**
	 * Published, then edited, so steps have staged changes
	 */
	protected function active_flow() {
		$funnel = new Funnel( [ 'title' => 'active', 'status' => 'active' ] );

		$this->add( $funnel, 'tag_applied', Step::BENCHMARK );
		$timer = $this->add( $funnel, 'delay_timer', Step::ACTION );
		$this->add( $funnel, 'apply_tag', Step::ACTION );

		$funnel->set_step_levels();
		$funnel->commit();

		$funnel->while_editing( function () use ( $funnel, $timer ) {
			( new Step( $timer->ID ) )->update( [ 'step_title' => 'Wait longer' ] );
			$this->add( $funnel, 'remove_tag', Step::ACTION, 'main', [ 'step_status' => 'inactive' ] );
			$funnel->set_step_levels();
		} );

		return $funnel;
	}

	/**
	 * @return Funnel[]
	 */
	protected function flows() {
		return [
			'empty'      => new Funnel( [ 'title' => 'empty', 'status' => 'inactive' ] ),
			'simple'     => $this->simple_flow(),
			'benchmarks' => $this->benchmarks_flow(),
			'branches'   => $this->branches_flow(),
			'active'     => $this->active_flow(),
		];
	}

	/**
	 * What flow-canvas.test.js needs to draw the flow, and the server's markup to compare it to
	 */
	protected function fixture( Funnel $funnel ) {
		return $funnel->while_editing( function () use ( $funnel ) {

			$steps = json_decode( wp_json_encode( $funnel->get_steps() ), true );

			// only the step types these steps use, their icons are big
			$step_types = array_intersect_key( Plugin::instance()->step_manager->get_elements(), array_flip( wp_list_pluck( wp_list_pluck( $steps, 'data' ), 'step_type' ) ) );

			return [
				'html'         => (string) $funnel->step_flow( false ),
				'steps'        => $steps,
				'canvas'       => (object) $funnel->get_canvas_data(),
				'step_types'   => json_decode( wp_json_encode( (object) $step_types ), true ),
				'default_icon' => GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/no-icon.png',
				'debug'        => WP_DEBUG,
			];
		} );
	}

	public function test_the_server_markup_matches_the_js_fixtures() {

		foreach ( $this->flows() as $name => $funnel ) {

			$fixture = $this->fixture( $funnel );
			$file    = $this->fixtures_dir() . "$name.json";

			if ( getenv( 'GH_UPDATE_JS_FIXTURES' ) ) {
				wp_mkdir_p( $this->fixtures_dir() );
				file_put_contents( $file, wp_json_encode( $fixture, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n" );
				$this->assertFileExists( $file );
				continue;
			}

			$this->assertFileExists( $file, 'Run with GH_UPDATE_JS_FIXTURES=1 to write the fixtures' );

			$saved = json_decode( file_get_contents( $file ), true );

			$this->assertSame( $saved['html'], $fixture['html'], "The server draws the $name flow differently than the JS fixture, run with GH_UPDATE_JS_FIXTURES=1 and check tests/js" );
		}
	}

	public function test_layouts() {

		$funnel = $this->branches_flow();

		$canvas = $funnel->while_editing( function () use ( $funnel ) {
			return $funnel->get_canvas_data();
		} );

		$layouts = [];

		foreach ( $funnel->get_steps() as $step ) {
			$layouts[ $step->get_type() ][] = $canvas[ $step->ID ]['layout'];
		}

		$this->assertEquals( [ 'benchmark' ], array_unique( $layouts['tag_applied'] ) );
		$this->assertEquals( [ 'branches' ], array_unique( $layouts['if_else'] ) );
		$this->assertEquals( [ 'default' ], array_unique( $layouts['delay_timer'] ) );

		// without Pro, the premium stop step is drawn like any other
		$this->assertEquals( [ 'default' ], $layouts['logic_stop'] );

		$if_else = array_values( array_filter( $funnel->get_steps(), function ( Step $step ) {
			return $step->get_type() === 'if_else';
		} ) )[0];

		$this->assertTrue( $canvas[ $if_else->ID ]['branch_logic'] );
		$this->assertEquals( [
			[ 'id' => "$if_else->ID-yes", 'name' => 'YES', 'classes' => 'green' ],
			[ 'id' => "$if_else->ID-no", 'name' => 'NO', 'classes' => 'red' ],
		], $canvas[ $if_else->ID ]['branches'] );
	}

	public function test_warnings_and_labels() {

		$funnel = $this->simple_flow();

		$canvas = $funnel->while_editing( function () use ( $funnel ) {
			return $funnel->get_canvas_data();
		} );

		[ $form, $email, $timer ] = $funnel->get_steps();

		$this->assertNotEmpty( $canvas[ $email->ID ]['errors'] );
		$this->assertContains( 'has-errors', $canvas[ $email->ID ]['classes'] );
		$this->assertContains( $canvas[ $email->ID ]['errors'][0]['code'], $canvas[ $email->ID ]['classes'] );

		$this->assertTrue( $canvas[ $timer->ID ]['locked'] );
		$this->assertStringContainsString( '<strong>a bit</strong>', $canvas[ $timer->ID ]['notes'] );
	}

	public function test_named_steps_are_flagged_for_the_editor() {

		$funnel = new Funnel( [ 'title' => 'named', 'status' => 'inactive' ] );

		$titled = $funnel->add_step( [ 'step_type' => 'apply_tag', 'step_group' => Step::ACTION, 'step_title' => 'Tags' ] );
		$note   = $funnel->add_step( [ 'step_type' => 'apply_note', 'step_group' => Step::ACTION, 'step_title' => 'A note' ] );

		$canvas = function () use ( $funnel ) {
			return $funnel->while_editing( function () use ( $funnel ) {
				return $funnel->get_canvas_data();
			} );
		};

		// apply_note has no generated title, so it's always named
		$this->assertFalse( $canvas()[ $titled->ID ]['named'] );
		$this->assertTrue( $canvas()[ $note->ID ]['named'] );

		update_option( 'gh_force_custom_step_names', 'on' );

		$this->assertTrue( $canvas()[ $titled->ID ]['named'] );
		$this->assertTrue( $canvas()[ $note->ID ]['named'] );

		// the editor draws the name's field, the settings from the server don't have it
		$island = $titled->get_step_element()->get_settings_island( $titled );
		$this->assertStringNotContainsString( "steps[{$titled->ID}][step_title]", $island['html'] );

		delete_option( 'gh_force_custom_step_names' );
	}

	public function test_hooks_print_on_the_card() {

		$funnel = $this->simple_flow();

		$labels = function ( Step $step ) {
			echo '<span class="my-label">' . esc_html( $step->ID ) . '</span>';
		};

		$classes = function ( $classes ) {
			$classes[] = 'my-class';

			return $classes;
		};

		add_action( 'groundhogg/steps/sortable/labels', $labels );
		add_action( 'groundhogg/steps/delay_timer/sortable/inside', $labels );
		add_filter( 'groundhogg/steps/sortable/classes', $classes );

		$canvas = $funnel->while_editing( function () use ( $funnel ) {
			return $funnel->get_canvas_data();
		} );

		remove_action( 'groundhogg/steps/sortable/labels', $labels );
		remove_action( 'groundhogg/steps/delay_timer/sortable/inside', $labels );
		remove_filter( 'groundhogg/steps/sortable/classes', $classes );

		[ $form, $email, $timer ] = $funnel->get_steps();

		$this->assertEquals( "<span class=\"my-label\">$form->ID</span>", $canvas[ $form->ID ]['extra_labels'] );
		$this->assertEquals( '', $canvas[ $form->ID ]['inside'] );
		$this->assertEquals( "<span class=\"my-label\">$timer->ID</span>", $canvas[ $timer->ID ]['inside'] );
		$this->assertContains( 'my-class', $canvas[ $email->ID ]['classes'] );
	}

	public function test_step_types_that_draw_themselves_send_their_html() {

		$type = new class extends Action {
			public function get_name() {
				return 'Custom card';
			}

			public function get_type() {
				return 'custom_card';
			}

			public function get_description() {
				return '';
			}

			public function settings( $step ) {
			}

			public function sortable_item( $step ) {
				echo '<div class="sortable-item custom-card">' . esc_html( $step->ID ) . '</div>';
			}
		};

		Plugin::instance()->step_manager->add_step( $type );

		$this->assertTrue( $type->uses_custom_sortable_item() );
		$this->assertFalse( Plugin::instance()->step_manager->get_element( 'delay_timer' )->uses_custom_sortable_item() );
		$this->assertFalse( Plugin::instance()->step_manager->get_element( 'if_else' )->uses_custom_sortable_item() );
		$this->assertFalse( Plugin::instance()->step_manager->get_element( 'tag_applied' )->uses_custom_sortable_item() );

		$funnel = new Funnel( [ 'title' => 'custom', 'status' => 'inactive' ] );
		$step   = $this->add( $funnel, 'custom_card', Step::ACTION );

		$canvas = $funnel->while_editing( function () use ( $funnel ) {
			return $funnel->get_canvas_data();
		} );

		$this->assertEquals( 'html', $canvas[ $step->ID ]['layout'] );
		$this->assertEquals( "<div class=\"sortable-item custom-card\">$step->ID</div>", $canvas[ $step->ID ]['html'] );
	}

	public function test_abilities_refuse_flows_someone_else_is_editing() {

		$funnel = $this->simple_flow();

		$this->assertTrue( Flow_Changes::check_lock( $funnel ) );

		$editor = self::factory()->user->create( [ 'role' => 'administrator', 'display_name' => 'Someone Else' ] );
		$me     = get_current_user_id();

		wp_set_current_user( $editor );
		set_lock( $funnel );
		wp_set_current_user( $me );

		$locked = Flow_Changes::check_lock( $funnel );

		$this->assertWPError( $locked );
		$this->assertEquals( 'groundhogg_flow_locked', $locked->get_error_code() );
		$this->assertStringContainsString( 'Someone Else', $locked->get_error_message() );

		if ( function_exists( 'wp_get_ability' ) && wp_get_ability( 'groundhogg/edit-flow' ) ) {
			$result = wp_get_ability( 'groundhogg/edit-flow' )->execute( [
				'flow_id'    => $funnel->ID,
				'operations' => [
					[ 'op' => 'delete', 'step' => $funnel->get_steps()[3]->ID ],
				],
			] );

			$this->assertWPError( $result );
			$this->assertEquals( 'groundhogg_flow_locked', $result->get_error_code() );
		}

		// the person editing it isn't locked out
		wp_set_current_user( $editor );
		$this->assertTrue( Flow_Changes::check_lock( $funnel ) );
	}
}
