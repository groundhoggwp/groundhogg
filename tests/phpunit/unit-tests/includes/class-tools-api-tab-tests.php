<?php

use Groundhogg\Admin\Tabbed_Admin_Page;
use Groundhogg\Admin\Tools\Tools_Page;

/**
 * The REST API Playground is a tab of the Tools page like the others, and is drawn with the title and the tabs
 *
 * It was left out of Tabbed_Admin_Page::page() by an override that printed only the playground.
 *
 * @see Tools_Page
 */
class Tools_Api_Tab_Tests extends GH_UnitTestCase {

	public function test_every_tab_is_drawn_by_the_tabbed_page() {
		$page = new ReflectionMethod( Tools_Page::class, 'page' );

		$this->assertSame( Tabbed_Admin_Page::class, $page->getDeclaringClass()->getName() );
	}

	public function test_the_playground_is_the_view_of_its_tab() {

		// the tabbed page looks for {tab}_view
		$this->assertTrue( method_exists( Tools_Page::class, 'api_view' ) );
	}

	public function test_the_playground_is_called_rest_api() {

		$source = file_get_contents( GROUNDHOGG_PATH . 'admin/tools/tools-page.php' );

		$this->assertStringContainsString( "'REST API Playground'", $source );
		$this->assertStringNotContainsString( 'Rest API', $source );
	}
}
