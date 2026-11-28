<?php

use function Groundhogg\html2markdown;

/**
 * html2markdown(), which makes the plain text version of emails: its text comes back as UTF-8 characters, whatever
 * the script and however the HTML wrote them, without text turning into markup
 */
class Html2Markdown_Tests extends GH_UnitTestCase {

	public function characters(): array {
		return [
			'accented letters'      => [ '<p>Se désabonner, voilà</p>', 'Se désabonner, voilà' ],
			'Arabic'                => [ '<p>إلغاء الاشتراك</p>', 'إلغاء الاشتراك' ],
			'Hebrew'                => [ '<p>ביטול המנוי</p>', 'ביטול המנוי' ],
			'Chinese'               => [ '<p>取消订阅</p>', '取消订阅' ],
			'Cyrillic'              => [ '<p>Отписаться</p>', 'Отписаться' ],
			'curly quotes, dashes'  => [ '<p>“Quoted” – and — ‘this’…</p>', '“Quoted” – and — ‘this’…' ],
			'emoji'                 => [ '<p>Hi 👋 🎉</p>', 'Hi 👋 🎉' ],
			'named entities'        => [ '<p>Se d&eacute;sabonner &ndash; &copy; &euro;</p>', 'Se désabonner – © €' ],
			'numeric entities'      => [ '<p>&#233;t&#xE9; &#128075;</p>', 'été 👋' ],
			'a non-breaking space'  => [ '<p>10&nbsp;km</p>', "10\u{00A0}km" ],
			'mixed in one'          => [ '<p>Привет, café, 你好 &amp; 👋</p>', 'Привет, café, 你好 & 👋' ],
		];
	}

	/**
	 * @dataProvider characters
	 */
	public function test_text_comes_back_as_characters( string $html, string $expected ) {
		$this->assertSame( $expected, html2markdown( $html ) );
	}

	public function test_text_that_looks_like_markup_stays_text() {

		// written as entities it's text, so it stays escaped and isn't stripped as a tag later
		$this->assertSame( 'Use &lt;b&gt; for **bold**', html2markdown( '<p>Use &lt;b&gt; for <b>bold</b></p>' ) );
		$this->assertSame( '1 &lt; 2 &gt; 0', html2markdown( '<p>1 &#60; 2 &#x3e; 0</p>' ) );

		// an escaped entity is decoded once, to the entity's text
		$this->assertSame( 'Write &amp;eacute; for é', html2markdown( '<p>Write &amp;amp;eacute; for &eacute;</p>' ) );
	}

	public function test_ampersands_and_merge_tags() {

		$this->assertSame( 'Tom & Jerry', html2markdown( '<p>Tom &amp; Jerry</p>' ) );
		$this->assertSame( '[Shop](https://example.com/?a=1&b=2)', html2markdown( '<a href="https://example.com/?a=1&amp;b=2">Shop</a>' ) );

		$this->assertSame( 'Hi {first}, [Unsubscribe](#unsubscribe_link#)', html2markdown( '<p>Hi {first}, <a href="#unsubscribe_link#">Unsubscribe</a></p>' ) );
		$this->assertSame( 'Bonjour {first}, [Se désabonner](#unsubscribe_link#).', html2markdown( '<p>Bonjour {first}, <a href="#unsubscribe_link#">Se désabonner</a>.</p>' ) );
	}

	public function test_the_structure_is_the_same() {
		// lines end in PHP_EOL, like they always have
		$this->assertSame( '# Titre é' . PHP_EOL . '**gras** et _italique_  ' . PHP_EOL . 'ligne', html2markdown( '<h1>Titre é</h1><p><b>gras</b> et <em>italique</em><br>ligne</p>' ) );
		$this->assertSame( '* Un' . PHP_EOL . ' * Deux', html2markdown( '<ul><li>Un</li><li>Deux</li></ul>' ) );
	}

	public function test_the_entities_can_be_kept() {
		// for a caller that decodes them itself, like the docs search
		$this->assertSame( 'Se d&eacute;sabonner &amp; &lt;b&gt;', html2markdown( '<p>Se désabonner &amp; &lt;b&gt;</p>', true, true, false ) );
	}

	public function test_without_cleaning_up() {
		$this->assertSame( 'Café & thé', html2markdown( 'Caf&eacute; &amp; thé', false ) );
	}
}
