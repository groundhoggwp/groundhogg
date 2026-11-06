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
			'/\[class\*="gh-theme-"\] \.notice a:not\(\.button\),\n[^{]*div\.updated a:not\(\.button\),\n[^{]*div\.error a:not\(\.button\) \{\s*color: var\(--ght-accent\);\s*\}/',
			$css
		);
	}

	public function test_a_button_in_a_notice_keeps_the_label_color_of_the_button() {

		$css = $this->css();

		// the accent link rule is more specific than the label color of a primary button, so on the accent background of
		// the button the label took the accent too, like the mobile app notice
		$this->assertDoesNotMatchRegularExpression( '/\.notice a(,| \{)/', $css );
		$this->assertDoesNotMatchRegularExpression( '/div\.(updated|error) a(,| \{)/', $css );
		$this->assertMatchesRegularExpression( '/\[class\*="gh-theme-"\] \.button-primary \{[^}]*[^-]color: var\(--ght-on-accent\);/', $css );
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

	public function test_the_canvas_of_the_email_editor_is_white_and_does_not_take_the_surface_of_its_panel() {

		$css = $this->css();

		// the text of the canvas is dark, so it can't be on the dark panel it is in. :where() keeps the specificity of
		// the rule at one id, so the background that the template CSS of an email sets on the canvas still wins
		$this->assertMatchesRegularExpression(
			'/:where\(body\.groundhogg-admin-page\[class\*="gh-theme-"\]\) #block-editor-content-wrap,\n[^{]*\{\s*background-color: #fff;\s*\}/',
			$css
		);
	}

	public function test_the_toolbar_and_frame_of_tinymce_take_the_colors_of_the_theme() {

		$css = $this->css();

		// the frame is in the page, the text is in an iframe that element.js gives the colors to
		$this->assertMatchesRegularExpression(
			'/\[class\*="gh-theme-"\] \.mce-tinymce,\n[^{]*\.mce-tinymce \.mce-panel,\n[^{]*\.mce-tinymce \.mce-statusbar \{\s*background-color: var\(--ght-surface\);/',
			$css
		);

		$this->assertMatchesRegularExpression(
			'/\.mce-tinymce \.mce-toolbar-grp,\n[^{]*\{\s*background-color: var\(--ght-raised\);/',
			$css
		);

		$this->assertMatchesRegularExpression(
			'/\.mce-tinymce \.mce-ico,\n[^{]*\{\s*color: var\(--ght-text\);/',
			$css
		);
	}

	public function test_the_menus_tabs_and_dropdown_of_the_editor_take_the_colors_of_the_theme() {

		$css = $this->css();

		// the menus of TinyMCE are in the body, so they aren't in its frame
		$this->assertMatchesRegularExpression(
			'/\.mce-floatpanel,\n[^{]*\.mce-floatpanel \.mce-menu,\n[^{]*\.mce-floatpanel \.mce-panel \{\s*background-color: var\(--ght-surface\);/',
			$css
		);

		$this->assertMatchesRegularExpression( '/\.mce-tinymce \.mce-btn\.mce-listbox \{\s*background-color: var\(--ght-surface\);/', $css );

		// the dropdown is before the hover rule, which has the same specificity, so hovering it still shows
		$this->assertLessThan( strpos( $css, '.mce-tinymce .mce-btn:hover' ), strpos( $css, '.mce-tinymce .mce-btn.mce-listbox' ) );

		$this->assertMatchesRegularExpression( '/\.wp-editor-tabs \.wp-switch-editor \{\s*background-color: var\(--ght-raised\);/', $css );
		$this->assertMatchesRegularExpression( '/\.wp-editor-container \{\s*background-color: var\(--ght-surface\);/', $css );
		$this->assertMatchesRegularExpression( '/\.wp-editor-wrap \.quicktags-toolbar \{\s*background-color: var\(--ght-raised\);/', $css );
	}
}
