<?php

/**
 * A button link that is danger has a red background. a.danger:hover is the red of a plain link, which is the red of
 * the hovered button, so the text of the button was red on red.
 */
class Admin_Danger_Button_Tests extends GH_UnitTestCase {

	public function test_a_danger_button_link_keeps_light_text_when_it_is_hovered() {

		foreach ( [ 'admin.scss', 'admin.css' ] as $file ) {

			$css = str_replace( "\r\n", "\n", file_get_contents( GROUNDHOGG_PATH . 'assets/css/admin/' . $file ) );

			$this->assertMatchesRegularExpression( '/a\.gh-button\.danger:hover,\s*a\.gh-button\.danger:focus\s*\{\s*color: (#fff|white);/', $css, $file );
		}
	}
}
