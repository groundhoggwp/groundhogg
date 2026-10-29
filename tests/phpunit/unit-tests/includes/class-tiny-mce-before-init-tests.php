<?php

use function Groundhogg\tiny_mce_before_init;

/**
 * The click listener that is added to TinyMCE replaces the setup of the editor, so it is only for the editors of
 * Groundhogg's pages. The editors of other pages, and the setup that other plugins give them, are left alone.
 *
 * @see tiny_mce_before_init()
 */
class Tiny_Mce_Before_Init_Tests extends GH_UnitTestCase {

	public function tearDown(): void {
		unset( $_GET['page'], $_REQUEST['page'] );
		set_current_screen( 'front' );
		parent::tearDown();
	}

	protected function visit( string $page ) {
		set_current_screen( 'dashboard' );
		$_GET['page'] = $_REQUEST['page'] = $page;
	}

	public function test_an_editor_of_a_groundhogg_page_gets_the_click_listener() {

		$this->visit( 'gh_contacts' );

		$settings = apply_filters( 'tiny_mce_before_init', [ 'selector' => '#notes' ], 'notes' );

		$this->assertSame( '#notes', $settings['selector'] );
		$this->assertStringContainsString( "jQuery(document).trigger( 'to_mce' )", $settings['setup'] );
	}

	public function test_an_editor_of_another_page_keeps_its_own_setup() {

		$setup = 'function(ed){ console.log( ed ) }';

		foreach ( [ 'some-other-plugin', 'wp-mail-smtp' ] as $page ) {
			$this->visit( $page );
			$this->assertSame( [ 'setup' => $setup ], apply_filters( 'tiny_mce_before_init', [ 'setup' => $setup ], 'content' ), $page );
		}

		// no page at all, like the editor of a post
		unset( $_GET['page'], $_REQUEST['page'] );
		$this->assertSame( [ 'setup' => $setup ], apply_filters( 'tiny_mce_before_init', [ 'setup' => $setup ], 'content' ) );

		// and no setup is added where there wasn't one
		$this->assertSame( [], tiny_mce_before_init( [] ) );
	}

	public function test_the_front_end_is_not_a_groundhogg_page_even_with_a_groundhogg_page_in_the_request() {

		set_current_screen( 'front' );
		$_GET['page'] = $_REQUEST['page'] = 'gh_contacts';

		$this->assertSame( [], tiny_mce_before_init( [] ) );
	}
}
