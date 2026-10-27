<?php

/**
 * What a release zip made with git archive has in it, which is decided by .gitattributes
 *
 * A machine that has core.autocrlf on would otherwise make every text file CRLF, and the dev files that .distignore
 * lists are only left out of a zip by a tool that reads it, which git archive doesn't.
 */
class Release_Archive_Tests extends GH_UnitTestCase {

	protected function attributes(): array {
		$lines = file( GROUNDHOGG_PATH . '.gitattributes', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );

		return array_values( array_filter( array_map( 'trim', $lines ), fn( $l ) => $l !== '' && $l[0] !== '#' ) );
	}

	public function test_text_files_are_lf_in_an_archive() {

		if ( ! file_exists( GROUNDHOGG_PATH . '.gitattributes' ) ) {
			$this->markTestSkipped( 'Not a git checkout' );
		}

		$this->assertContains( '* text=auto eol=lf', $this->attributes() );
	}

	public function test_the_dev_files_are_not_in_an_archive() {

		if ( ! file_exists( GROUNDHOGG_PATH . '.gitattributes' ) ) {
			$this->markTestSkipped( 'Not a git checkout' );
		}

		$attributes = $this->attributes();

		foreach ( [ '/mobile-app', '/README.md', '/tests', '/bin', '/node_modules' ] as $path ) {
			$this->assertContains( "$path export-ignore", $attributes );
		}
	}

	public function test_what_distignore_leaves_out_git_archive_leaves_out() {

		if ( ! file_exists( GROUNDHOGG_PATH . '.distignore' ) || ! file_exists( GROUNDHOGG_PATH . '.gitattributes' ) ) {
			$this->markTestSkipped( 'Not a git checkout' );
		}

		$ignored    = array_filter( array_map( 'trim', file( GROUNDHOGG_PATH . '.distignore' ) ) );
		$attributes = $this->attributes();

		// only the things in the root of the plugin that start with a slash, the others are patterns
		foreach ( [ '/mobile-app', '/tests', '/bin', '/vendor' ] as $path ) {
			$this->assertContains( $path, $ignored );
			$this->assertContains( "$path export-ignore", $attributes );
		}
	}
}
