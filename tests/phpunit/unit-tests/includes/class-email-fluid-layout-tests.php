<?php

use Groundhogg\Email;
use function Groundhogg\make_email_images_fluid;
use function Groundhogg\remove_font_family_quotes;
use function Groundhogg\repair_font_family_quotes;
use function Groundhogg\restore_font_family_quotes;

/**
 * Emails must hold up in clients that drop <style> blocks, and the saved block CSS must be valid.
 *
 * Saved email HTML/CSS is not regenerated until an email is edited, so the fixes here have to apply to
 * content that is already stored.
 */
class Email_Fluid_Layout_Tests extends GH_UnitTestCase {

	/**
	 * A rule whose last declaration is a font-family (the editor drops the final `;`) must survive a save.
	 */
	public function test_font_family_quotes_do_not_run_past_the_end_of_a_rule() {

		$css = '#b-5 a{color:#000;font-family:Arial, sans-serif}#b-6 p{color:#111;font-size:14px}';

		$this->assertSame( $css, restore_font_family_quotes( remove_font_family_quotes( $css ) ) );
	}

	public function test_multi_word_fonts_are_still_quoted_inline() {

		$html = '<p style="font-family:Trebuchet MS, sans-serif;color:#000">x</p>';

		$this->assertSame(
			'<p style="font-family:\'Trebuchet MS\', sans-serif;color:#000">x</p>',
			restore_font_family_quotes( remove_font_family_quotes( $html ) )
		);
	}

	public function test_repair_undoes_mangled_font_family_at_the_end_of_the_css() {

		$this->assertSame(
			'#b-1 p{font-size:16px}#b-27 a{color:#000000;font-family:Arial, sans-serif}',
			repair_font_family_quotes( '#b-1 p{font-size:16px}#b-27 a{color:#000000;font-family:Arial, \'sans-serif}\'' )
		);
	}

	public function test_repair_restores_the_rule_that_followed_a_mangled_one() {

		$this->assertSame(
			'#b-5 a{color:#000;font-family:Arial, sans-serif}#b-6 p{color:#111;font-size:14px}',
			repair_font_family_quotes( '#b-5 a{color:#000;font-family:Arial, \'sans-serif}#b-6 p{color:#111\';font-size:14px}' )
		);
	}

	public function test_repair_leaves_valid_css_alone() {

		$css = '#b-1 p{font-family:\'Trebuchet MS\', sans-serif;font-size:16px}#b-2 a{font-family:Arial, sans-serif}';

		$this->assertSame( $css, repair_font_family_quotes( $css ) );
	}

	public function test_email_get_css_repairs_previously_saved_css() {

		$email = new Email( [
			'subject' => 'subject',
			'title'   => 'title',
			'content' => '<p>content</p>',
		] );

		// write straight to the meta table, like an email saved before the fix (the sanitizer now cleans this up itself)
		\Groundhogg\get_db( 'emailmeta' )->add_meta(
			$email->get_id(),
			'css',
			'#b-5 a{color:#000;font-family:Arial, \'sans-serif}#b-6 p{color:#111\';font-size:14px}'
		);

		$this->assertStringContainsString(
			'#b-5 a{color:#000;font-family:Arial, sans-serif}#b-6 p{color:#111;font-size:14px}',
			( new Email( $email->get_id() ) )->get_css()
		);
	}

	public function test_images_with_auto_height_get_an_inline_max_width_and_lose_the_invalid_height() {

		$html = '<img src="a.jpg" alt="" width="640" height="auto" style="box-sizing:border-box;vertical-align:bottom;height:auto;width:640px;">';

		$out = make_email_images_fluid( $html );

		$this->assertStringNotContainsString( 'height="auto"', $out );
		$this->assertStringContainsString( 'height:auto;width:640px;max-width:100%', $out );
		$this->assertStringContainsString( 'width="640"', $out );
	}

	public function test_images_with_only_the_auto_height_attribute_are_made_fluid() {

		$out = make_email_images_fluid( '<img src="a.jpg" width="206" height="auto">' );

		$this->assertStringNotContainsString( 'height="auto"', $out );
		$this->assertStringContainsString( 'style="max-width:100%"', $out );
	}

	public function test_images_with_a_max_width_are_untouched() {

		$html = '<img src="a.jpg" width="206" style="height:auto;max-width:50%">';

		$this->assertSame( $html, make_email_images_fluid( $html ) );
	}

	public function test_fixed_size_images_are_untouched() {

		$html = '<img src="icon.png" width="20" height="20" style="vertical-align:bottom"><img src="px.gif" width="0" height="0" alt="">';

		$this->assertSame( $html, make_email_images_fluid( $html ) );
	}

	public function test_merged_block_content_is_made_fluid_at_render_time() {

		$email = new Email( [
			'subject'     => 'subject',
			'title'       => 'title',
			'content'     => '<table><tr><td><img src="a.jpg" width="640" height="auto" style="height:auto;width:640px"></td></tr></table>',
		] );

		$email->add_meta( 'blocks', true );
		$email = new Email( $email->get_id() );

		$content = $email->get_merged_content();

		$this->assertStringNotContainsString( 'height="auto"', $content );
		$this->assertStringContainsString( 'max-width:100%', $content );
	}
}
