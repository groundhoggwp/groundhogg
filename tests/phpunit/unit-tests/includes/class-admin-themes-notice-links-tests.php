<?php

/**
 * Links in a notice take the accent of the theme, WordPress's blue is 2:1 on the notices of the dark themes
 */
class Admin_Themes_Notice_Links_Tests extends GH_UnitTestCase {

	protected function css(): string {
		return str_replace( "\r\n", "\n", file_get_contents( GROUNDHOGG_PATH . 'assets/css/admin/themes.css' ) );
	}

	public function test_links_in_the_notices_take_the_accent() {

		$css = $this->css();

		$this->assertMatchesRegularExpression(
			'/\[class\*="gh-theme-"\] \.notice a,\n[^{]*div\.updated a,\n[^{]*div\.error a \{\s*color: var\(--ght-accent\);\s*\}/',
			$css
		);
	}

	public function test_the_yellow_promo_card_keeps_dark_text_in_every_theme() {

		$css = $this->css();

		// the card is yellow whatever the theme is, and the headings and text of the dark themes are light
		$this->assertMatchesRegularExpression(
			'/\[class\*="gh-theme-"\] \.gh-site \.gh-primary-card h3,
[^{]*\.gh-site \.gh-primary-card p \{\s*color: var\(--color-dark-900\);\s*\}/',
			$css
		);
	}

	public function test_every_theme_has_an_accent_and_notice_backgrounds_to_read_it_on() {

		$css = $this->css();

		foreach ( [ 'light', 'paper', 'slate', 'midnight' ] as $theme ) {

			$this->assertSame( 1, preg_match( '/body\.groundhogg-admin-page\.gh-theme-' . $theme . ' \{(.*?)\n\}/s', $css, $block ), $theme );

			foreach ( [ '--ght-accent', '--ght-green-bg', '--ght-red-bg', '--ght-yellow-bg', '--ght-surface' ] as $variable ) {
				$this->assertStringContainsString( $variable . ':', $block[1], "$theme $variable" );
			}
		}
	}
}
