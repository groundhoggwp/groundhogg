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

	public function test_the_steps_of_the_guided_setup_take_the_text_color_of_the_theme() {

		$this->assertMatchesRegularExpression(
			'/#guided-setup \.gs-stepper \.done \.gs-stepper-step,\n[^{]*#guided-setup \.gs-stepper \.current \.gs-stepper-step \{\s*color: var\(--ght-text\);/',
			$this->css()
		);
	}

	public function test_the_letters_of_the_logo_take_the_text_color_of_the_theme() {

		$css = $this->css();

		foreach ( [ '#guided-setup .gs-logo svg #shine > g:first-child path', '#troubleshooter svg #shine > g:first-child path' ] as $selector ) {
			$this->assertStringContainsString( $selector, $css );
		}

		// the letters are the paths of the first group of the logo, the bear is in the second
		$icons = file_get_contents( GROUNDHOGG_PATH . 'assets/js/admin/element.js' );
		$this->assertSame( 1, preg_match( '/groundhogg_black: `(.*?)`,/s', $icons, $logo ) );
		$this->assertMatchesRegularExpression( '/<g id="shine">\s*<g>\s*<path\s+d=/', $logo[1] );
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
