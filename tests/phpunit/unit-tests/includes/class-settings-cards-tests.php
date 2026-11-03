<?php

use Groundhogg\Admin\Settings\Settings_Page;

/**
 * The tabs of the settings are printed as cards of rows, not as a table for each section
 */
class Settings_Cards_Tests extends GH_UnitTestCase {

	public function set_up() {
		parent::set_up();

		global $wp_settings_sections, $wp_settings_fields;
		$wp_settings_sections = [];
		$wp_settings_fields   = [];

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	/**
	 * The page with its settings registered, the sections are the defaults plus any added by the test
	 */
	protected function settings_page(): Settings_Page {
		$page = new Settings_Page();
		$page->init_defaults();
		$page->register_sections();
		$page->register_settings();

		return $page;
	}

	protected function render( Settings_Page $page, string $tab ): string {
		$method = new ReflectionMethod( Settings_Page::class, 'do_sections' );
		$method->setAccessible( true );

		ob_start();
		$method->invoke( $page, 'gh_' . $tab );

		return ob_get_clean();
	}

	public function test_a_tab_is_printed_as_cards_of_rows() {

		$html = $this->render( $this->settings_page(), 'general' );

		$this->assertStringContainsString( 'class="gh-settings-sections"', $html );
		$this->assertStringContainsString( 'gh-settings-section', $html );
		$this->assertStringContainsString( 'gh-settings-row-label', $html );
		$this->assertStringContainsString( 'name="gh_business_name"', $html );
		$this->assertStringNotContainsString( 'form-table', $html );
	}

	public function test_every_section_is_a_card_with_its_title() {

		$html = $this->render( $this->settings_page(), 'general' );

		$this->assertMatchesRegularExpression( '/<h2>Business Settings<\/h2>/', $html );
		$this->assertMatchesRegularExpression( '/<h2>Other<\/h2>/', $html );
	}

	public function test_a_section_that_prints_something_has_it_ahead_of_its_fields() {

		add_filter( 'groundhogg/admin/settings/sections', $add = function ( $sections ) {
			$sections['test_notice'] = [
				'id'       => 'test_notice',
				'title'    => 'Test Notice',
				'tab'      => 'general',
				'callback' => function () {
					echo '<p class="test-notice">Look at this</p>';
				},
			];

			return $sections;
		} );

		$html = $this->render( $this->settings_page(), 'general' );
		remove_filter( 'groundhogg/admin/settings/sections', $add );

		$this->assertStringContainsString( '<div class="gh-settings-section-intro"><p class="test-notice">Look at this</p></div>', $html );
	}

	public function test_a_section_that_only_prints_a_script_has_no_empty_intro() {

		add_filter( 'groundhogg/admin/settings/sections', $add = function ( $sections ) {
			$sections['test_script'] = [
				'id'       => 'test_script',
				'title'    => 'Test Script',
				'tab'      => 'general',
				'callback' => function () {
					echo '<script>window.testScriptRan = true</script>';
				},
			];

			return $sections;
		} );

		add_filter( 'groundhogg/admin/settings/settings', $add_setting = function ( $settings ) {
			$settings['gh_test_script_setting'] = [
				'section' => 'test_script',
				'label'   => 'Test Setting',
				'type'    => 'input',
			];

			return $settings;
		} );

		$html = $this->render( $this->settings_page(), 'general' );
		remove_filter( 'groundhogg/admin/settings/sections', $add );
		remove_filter( 'groundhogg/admin/settings/settings', $add_setting );

		$this->assertStringContainsString( '<script>window.testScriptRan = true</script>', $html );
		$this->assertStringNotContainsString( 'gh-settings-section-intro', $html );
		$this->assertStringContainsString( 'name="gh_test_script_setting"', $html );
	}

	public function test_a_section_with_nothing_in_it_is_not_printed() {

		add_filter( 'groundhogg/admin/settings/sections', $add = function ( $sections ) {
			$sections['test_empty'] = [
				'id'       => 'test_empty',
				'title'    => 'Test Empty',
				'tab'      => 'general',
				'callback' => function () {
					echo '   ';
				},
			];

			return $sections;
		} );

		$html = $this->render( $this->settings_page(), 'general' );
		remove_filter( 'groundhogg/admin/settings/sections', $add );

		$this->assertStringNotContainsString( 'Test Empty', $html );
	}

	public function test_a_field_without_a_label_takes_the_whole_row() {

		add_filter( 'groundhogg/admin/settings/settings', $add = function ( $settings ) {
			$settings['gh_test_wide_setting'] = [
				'section' => 'business_info',
				'label'   => '',
				'type'    => 'input',
			];

			return $settings;
		} );

		$html = $this->render( $this->settings_page(), 'general' );
		remove_filter( 'groundhogg/admin/settings/settings', $add );

		$this->assertMatchesRegularExpression( '/<div class="gh-settings-row wide">\s*<div class="gh-settings-row-control">\s*<span id="setting-gh_test_wide_setting"/', $html );
	}

	public function test_the_danger_zone_is_a_red_card_and_the_last_one_of_its_tab() {

		$html = $this->render( $this->settings_page(), 'misc' );

		$this->assertStringContainsString( 'gh-settings-section danger', $html );

		// the red card is the only one that is, and nothing comes after it
		$this->assertSame( 1, substr_count( $html, 'gh-settings-section danger' ) );

		$danger = strpos( $html, 'gh-settings-section danger' );
		$this->assertStringNotContainsString( 'class="gh-panel gh-settings-section ', substr( $html, $danger + 10 ) );
		$this->assertMatchesRegularExpression( '/<h2>Danger Zone<\/h2>/', $html );
	}

	public function test_a_section_marked_as_dangerous_goes_last_whatever_its_order() {

		add_filter( 'groundhogg/admin/settings/sections', $add = function ( $sections ) {
			$sections = array_merge( [
				'test_danger' => [
					'id'       => 'test_danger',
					'title'    => 'Test Danger',
					'tab'      => 'general',
					'danger'   => true,
					'callback' => function () {
						echo '<p>Careful</p>';
					},
				]
			], $sections );

			return $sections;
		} );

		$html = $this->render( $this->settings_page(), 'general' );
		remove_filter( 'groundhogg/admin/settings/sections', $add );

		$this->assertGreaterThan( strpos( $html, '<h2>Other</h2>' ), strpos( $html, '<h2>Test Danger</h2>' ) );
	}

	public function test_the_api_tab_is_wrapped_in_a_container_of_cards() {

		$page = $this->settings_page();

		ob_start();
		$page->api_sections_open();
		$page->api_routes();
		$page->api_sections_close();
		$html = ob_get_clean();

		$this->assertStringStartsWith( '<div class="gh-settings-sections">', $html );
		$this->assertStringContainsString( '<h2>REST API</h2>', $html );
		$this->assertStringContainsString( 'API v4 Route', $html );
		$this->assertStringEndsWith( '</div>', trim( $html ) );
	}
}
