<?php

use Groundhogg\Plugin;

/**
 * The scripts that have strings to translate are given their text domain when they are registered. A handle in the list
 * that isn't registered is skipped without a notice, and the strings of its script stay in English, which is what
 * happened to the saved replies script when its handle was written as groundhogg-admin-replies.
 *
 * @see \Groundhogg\Scripts::register_admin_scripts()
 */
class Script_Translations_Tests extends GH_UnitTestCase {

	public function tearDown(): void {
		set_current_screen( 'front' );
		parent::tearDown();
	}

	public function test_the_admin_scripts_that_have_strings_get_the_text_domain() {

		set_current_screen( 'dashboard' );
		Plugin::$instance->scripts->register_admin_scripts();

		$scripts = wp_scripts();

		foreach ( [
			'groundhogg-make-el',
			'groundhogg-admin-components',
			'groundhogg-admin-saved-replies', // components/replies.js
			'groundhogg-admin-filter-contacts', // registered in a loop over the filters
			'groundhogg-admin-dashboard',
		] as $handle ) {
			$this->assertArrayHasKey( $handle, $scripts->registered, $handle );
			$this->assertSame( 'groundhogg', $scripts->registered[ $handle ]->textdomain, $handle );
		}
	}

	public function test_the_list_of_scripts_to_translate_has_no_handle_that_is_not_registered() {

		set_current_screen( 'dashboard' );
		Plugin::$instance->scripts->register_admin_scripts();

		$source = file_get_contents( GROUNDHOGG_PATH . 'includes/scripts.php' );

		$this->assertSame( 1, preg_match( '/foreach \( \[(.*?)\] as \$handle \) \{\s*wp_set_script_translations\(/s', $source, $list ) );
		$this->assertGreaterThan( 20, preg_match_all( "/'(groundhogg[a-z0-9-]*)'/", $list[1], $handles ) );

		foreach ( $handles[1] as $handle ) {
			$this->assertArrayHasKey( $handle, wp_scripts()->registered, "$handle is in the list of scripts to translate but isn't registered" );
		}
	}
}
