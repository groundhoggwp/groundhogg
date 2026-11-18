<?php

use Groundhogg\Plugin;

/**
 * The app's manifest icons. A browser only offers to install the app with a square icon of 192 and 512, so the
 * fallbacks have to be square, exist, and be listed whether or not the site is white labeled.
 */
class Mobile_App_Icons_Tests extends GH_UnitTestCase {

	protected function app() {
		return Plugin::$instance->mobile;
	}

	public function tear_down() {
		remove_all_filters( 'get_site_icon_url' );
		parent::tear_down();
	}

	protected function sizes( array $icons ): array {
		return array_column( $icons, 'sizes' );
	}

	public function test_without_a_site_icon_the_brand_has_square_192_and_512_icons_that_exist() {

		$icons = $this->app()->get_icons( false );

		$this->assertContains( '192x192', $this->sizes( $icons ) );
		$this->assertContains( '512x512', $this->sizes( $icons ) );
		$this->assertContains( 'maskable', array_column( $icons, 'purpose' ) );

		foreach ( $icons as $icon ) {
			$file = str_replace( GROUNDHOGG_ASSETS_URL, GROUNDHOGG_ASSETS_PATH, $icon['src'] );

			$this->assertFileExists( $file );

			$size = getimagesize( $file );

			$this->assertSame( $icon['sizes'], $size[0] . 'x' . $size[1], $icon['src'] );
		}
	}

	public function test_a_white_labeled_site_without_a_site_icon_still_has_icons() {

		$icons = $this->app()->get_icons( true );

		$this->assertNotEmpty( $icons );

		foreach ( $icons as $icon ) {
			$this->assertSame( 'image/svg+xml', $icon['type'] );
			$this->assertSame( 'any', $icon['sizes'] );
			$this->assertStringNotContainsString( 'groundhogg-icon', $icon['src'] );
			$this->assertStringNotContainsString( 'images/mobile', $icon['src'] );
		}

		$this->assertSame( [ 'any', 'maskable' ], array_column( $icons, 'purpose' ) );
	}

	public function test_the_generated_icon_is_the_first_letter_of_the_domain_on_the_apps_color() {

		$svg = \Groundhogg\Mobile\App::generated_icon_svg();

		$this->assertStringStartsWith( '<svg', $svg );
		$this->assertStringContainsString( strtoupper( substr( \Groundhogg\Mobile\App::domain(), 0, 1 ) ) . '</text>', $svg );
		$this->assertStringContainsString( \Groundhogg\Mobile\App::color(), $svg );
		$this->assertNotFalse( simplexml_load_string( $svg ), 'It is valid XML' );
	}

	public function test_the_site_icon_wins() {

		add_filter( 'get_site_icon_url', function ( $url, $size ) {
			return "https://example.test/icon-$size.png";
		}, 10, 2 );

		foreach ( [ true, false ] as $white_labeled ) {
			$icons = $this->app()->get_icons( $white_labeled );

			$this->assertSame( [ '192x192', '512x512' ], $this->sizes( $icons ) );
			$this->assertSame( 'https://example.test/icon-192.png', $icons[0]['src'] );
		}
	}
}
