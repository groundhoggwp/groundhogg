<?php

use Groundhogg\Abilities\Utils\Get_Authoring_Guide;
use Groundhogg\Mcp\Build_With_AI;

/**
 * The persona and ground rules for a session: the "assistant" authoring guide, and the lines at the top of every prompt
 * from the "Build with AI" popups that send the assistant to it.
 */
class Assistant_Guide_Tests extends GH_UnitTestCase {

	public function tear_down() {
		remove_all_filters( 'groundhogg/build_with_ai/assistant_name' );
		parent::tear_down();
	}

	public function test_every_prompt_starts_with_the_persona_and_the_guide() {

		foreach ( Build_With_AI::TYPES as $type ) {

			$prompt = Build_With_AI::prompt( $type, [ 'id' => 1, 'title' => 'Welcome' ] );
			$first  = strtok( $prompt, "\n" );

			$this->assertStringContainsString( 'You are PhilBot', $first, $type );
			$this->assertStringContainsString( 'topic "assistant"', $prompt, $type );
			$this->assertStringContainsString( '{{goal}}', $prompt, $type );
		}
	}

	public function test_the_assistant_can_be_renamed_or_left_unnamed() {

		add_filter( 'groundhogg/build_with_ai/assistant_name', function () {
			return 'Hoggy';
		} );

		$this->assertStringStartsWith( 'You are Hoggy,', Build_With_AI::prompt( 'email' ) );
		$this->assertStringContainsString( "I'm Hoggy.", Get_Authoring_Guide::content( 'assistant' ) );

		remove_all_filters( 'groundhogg/build_with_ai/assistant_name' );
		add_filter( 'groundhogg/build_with_ai/assistant_name', '__return_empty_string' );

		$this->assertStringStartsWith( 'You are an AI assistant for the CRM', Build_With_AI::prompt( 'email' ) );
		$this->assertStringContainsString( "I'm the CRM assistant.", Get_Authoring_Guide::content( 'assistant' ) );
	}

	public function test_the_guide_has_no_placeholders_left() {

		$content = Get_Authoring_Guide::content( 'assistant' );

		$this->assertStringContainsString( 'PhilBot', $content );
		$this->assertStringNotContainsString( '{{', $content );
		$this->assertStringContainsString( 'page=gh_help', $content );
	}

	public function test_branded_parts_are_left_out_when_white_labeled() {

		$markdown = file_get_contents( Get_Authoring_Guide::guides()['assistant']['file'] );

		$branded = Get_Authoring_Guide::apply_branding( $markdown, true );
		$white   = Get_Authoring_Guide::apply_branding( $markdown, false );

		$this->assertStringContainsString( 'Groundhogg', $branded );
		$this->assertStringContainsString( 'groundhogg.io', $branded );
		$this->assertStringNotContainsString( "Don't name the plugin", $branded );

		$this->assertStringNotContainsString( 'Groundhogg', $white );
		$this->assertStringNotContainsString( 'groundhogg.io', $white );
		$this->assertStringNotContainsString( '{{help_url}}', $white );
		$this->assertStringContainsString( "Don't name the plugin", $white );

		$this->assertStringNotContainsString( 'branded}}', $branded . $white );
		$this->assertStringNotContainsString( "\n\n\n", $branded . $white );
	}

	/**
	 * Products to recommend, and the sending services, name names only where the site isn't white labeled
	 */
	public function test_recommendations_are_left_out_when_white_labeled() {

		foreach ( [ 'assistant', 'setup' ] as $topic ) {

			$markdown = file_get_contents( Get_Authoring_Guide::guides()[ $topic ]['file'] );

			$branded = Get_Authoring_Guide::apply_branding( $markdown, true );
			$white   = Get_Authoring_Guide::apply_branding( $markdown, false );

			$this->assertStringContainsString( 'MailHawk', $branded, $topic );
			$this->assertStringContainsString( 'SendGrid', $branded, $topic );

			foreach ( [ 'MailHawk', 'SendGrid', 'Amazon SES', 'WooCommerce', 'LearnDash', 'MemberPress' ] as $name ) {
				$this->assertStringNotContainsString( $name, $white, "$topic: $name" );
			}

			// bounces and complaints are explained either way
			$this->assertStringContainsString( 'FluentSMTP', $white, $topic );
		}

		$assistant = Get_Authoring_Guide::content( 'assistant' );

		$this->assertStringContainsString( 'WooCommerce', $assistant );
		$this->assertStringContainsString( 'LearnDash', $assistant );
		$this->assertStringContainsString( 'BuddyBoss', $assistant );

		// integrations we don't recommend, only mention to people who already use them
		$this->assertStringContainsString( 'Don\'t recommend them', $assistant );
		$this->assertStringNotContainsString( 'LifterLMS and Tutor LMS', $assistant );
		$this->assertStringContainsString( 'Only write an HTML email when the person asks for HTML outright', $assistant );
	}

	/**
	 * Upgrades are recommended as plans, never as add-ons bought on their own, and only where the site isn't white
	 * labeled
	 */
	public function test_upgrades_are_recommended_as_plans_when_branded() {

		$markdown = file_get_contents( Get_Authoring_Guide::guides()['assistant']['file'] );

		$branded = Get_Authoring_Guide::apply_branding( $markdown, true );
		$white   = Get_Authoring_Guide::apply_branding( $markdown, false );

		$this->assertStringContainsString( '## Plans and upgrading', $branded );
		$this->assertStringContainsString( 'Never recommend buying an add-on on its own', $branded );
		$this->assertMatchesRegularExpression( '/\*\*Pro\*\*: the store, course and membership integrations \(WooCommerce/', $branded );
		$this->assertMatchesRegularExpression( '/\*\*Pro\*\*:.*text messaging/', $branded );

		foreach ( [ 'Plans and upgrading', 'pricing', '**Basic**', '**Pro**', 'upgrade' ] as $text ) {
			$this->assertStringNotContainsString( $text, $white, $text );
		}
	}

	/**
	 * The docs are ours, so the guides only send the assistant to them where the site isn't white labeled
	 */
	public function test_the_docs_are_only_mentioned_when_branded() {

		foreach ( [ 'assistant', 'setup' ] as $topic ) {

			$markdown = file_get_contents( Get_Authoring_Guide::guides()[ $topic ]['file'] );

			$this->assertStringContainsString( 'search-docs', Get_Authoring_Guide::apply_branding( $markdown, true ), $topic );
			$this->assertStringNotContainsString( 'search-docs', Get_Authoring_Guide::apply_branding( $markdown, false ), $topic );
		}
	}

	/**
	 * The strategy guide sends the assistant to reports by id, so they have to be real
	 */
	public function test_the_strategy_guide_only_names_reports_that_exist() {

		$markdown = file_get_contents( Get_Authoring_Guide::guides()['strategy']['file'] );

		preg_match_all( '/`((?:total|num|chart|table)_[a-z_]+)`/', $markdown, $matches );

		$this->assertNotEmpty( $matches[1] );

		$reports = \Groundhogg\Reports::get_registered_reports();

		foreach ( array_unique( $matches[1] ) as $report ) {
			$this->assertContains( $report, $reports, "$report isn't a report" );
		}
	}

	/**
	 * A mission is only offered to people who have every capability its work needs
	 */
	public function test_missions_are_scoped_to_capabilities() {

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$all = wp_list_pluck( Build_With_AI::examples( 'strategy' ), 'label' );

		$this->assertCount( 8, $all );

		// a sales rep works with contacts, not reports, flows or tags
		$user = self::factory()->user->create( [ 'role' => 'sales_rep' ] );
		wp_set_current_user( $user );

		foreach ( Build_With_AI::examples( 'strategy' ) as $mission ) {
			foreach ( $mission['caps'] as $cap ) {
				$this->assertTrue( user_can( $user, $cap ), "{$mission['label']} needs $cap" );
			}
		}

		$this->assertLessThan( count( $all ), count( Build_With_AI::examples( 'strategy' ) ) );

		// every mission says what it needs
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		foreach ( Build_With_AI::examples( 'strategy' ) as $mission ) {
			$this->assertNotEmpty( $mission['caps'], $mission['label'] );
		}
	}

	public function test_the_strategy_prompt_and_missions() {

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$prompt = Build_With_AI::prompt( 'strategy' );

		$this->assertStringContainsString( 'topic "strategy"', $prompt );
		$this->assertStringContainsString( 'Until I agree to a change, only look', $prompt );

		$missions = Build_With_AI::examples( 'strategy' );

		$this->assertGreaterThanOrEqual( 6, count( $missions ) );

		foreach ( $missions as $mission ) {
			$this->assertNotEmpty( $mission['label'] );
			$this->assertNotEmpty( $mission['summary'] );
			$this->assertNotEmpty( $mission['text'] );
		}

		$this->assertStringNotContainsString( '{{', Get_Authoring_Guide::content( 'strategy' ) );
		$this->assertSame( 'view_contacts', Get_Authoring_Guide::guides()['strategy']['capability'] );
	}

	/**
	 * The AI agent tab offers building emails, flows and the setup only to people who can build them
	 */
	public function test_build_cards_are_offered_by_capability() {

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		foreach ( [ 'email', 'flow', 'setup' ] as $type ) {
			$this->assertTrue( Build_With_AI::can_build( $type ), $type );
		}

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		foreach ( [ 'email', 'flow', 'setup', 'strategy' ] as $type ) {
			$this->assertFalse( Build_With_AI::can_build( $type ), $type );
		}
	}

	public function test_anyone_who_can_connect_an_assistant_can_read_it() {

		$ability = wp_get_ability( 'groundhogg/get-authoring-guide' );

		if ( ! $ability ) {
			$this->markTestSkipped( 'Needs WordPress 6.9 for the Abilities API.' );
		}

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'sales_manager' ] ) );

		$result = $ability->execute( [ 'topic' => 'assistant' ] );

		$this->assertNotWPError( $result );
		$this->assertStringContainsString( 'Ground rules', $result['content'] );

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$this->assertWPError( $ability->execute( [ 'topic' => 'assistant' ] ) );
	}

	public function test_the_troubleshoot_prompt_and_missions() {

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$prompt = Build_With_AI::prompt( 'troubleshoot' );

		$this->assertStringContainsString( 'topic "troubleshoot"', $prompt );
		$this->assertStringContainsString( 'Until I agree to a change, only look', $prompt );

		$missions = Build_With_AI::examples( 'troubleshoot' );

		$this->assertGreaterThanOrEqual( 6, count( $missions ) );

		foreach ( $missions as $mission ) {
			foreach ( [ 'id', 'label', 'summary', 'text', 'caps' ] as $key ) {
				$this->assertNotEmpty( $mission[ $key ], "{$mission['label']}: $key" );
			}
		}

		$ids = wp_list_pluck( $missions, 'id' );
		$this->assertSame( $ids, array_unique( $ids ) );

		$this->assertStringNotContainsString( '{{', Get_Authoring_Guide::content( 'troubleshoot' ) );
		$this->assertSame( 'view_contacts', Get_Authoring_Guide::guides()['troubleshoot']['capability'] );
	}

	/**
	 * The troubleshooter offers a mission on the step for the same problem, by its id, so every mission needs a step,
	 * and every step's needs a mission
	 */
	public function test_troubleshoot_missions_match_the_troubleshooters_steps() {

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$script = file_get_contents( GROUNDHOGG_PATH . 'assets/js/admin/features/troubleshooter.js' );

		// a step's ai is a mission's id, or a function that picks one
		preg_match_all( '/\bai: [^\n]+/', $script, $lines );
		preg_match_all( "/'([a-z-]+)'/", implode( "\n", $lines[0] ), $matches );

		$steps    = array_diff( array_unique( $matches[1] ), [ 'other' ] );
		$missions = wp_list_pluck( Build_With_AI::examples( 'troubleshoot' ), 'id' );

		$this->assertNotEmpty( $steps );

		foreach ( $steps as $step ) {
			$this->assertContains( $step, $missions, "the troubleshooter offers $step" );
		}

		foreach ( $missions as $mission ) {
			$this->assertContains( $mission, $steps, "no step offers $mission" );
		}
	}

	/**
	 * Most troubleshooting needs an administrator's tools, so troubleshooting is offered to anyone else only for the
	 * problems they can look into
	 */
	public function test_troubleshooting_is_offered_by_capability() {

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$this->assertTrue( Build_With_AI::can_offer( 'troubleshoot' ) );

		$all = count( Build_With_AI::examples( 'troubleshoot' ) );

		$user = self::factory()->user->create( [ 'role' => 'marketer' ] );
		wp_set_current_user( $user );

		foreach ( Build_With_AI::examples( 'troubleshoot' ) as $mission ) {
			foreach ( $mission['caps'] as $cap ) {
				$this->assertTrue( user_can( $user, $cap ), "{$mission['label']} needs $cap" );
			}
		}

		$this->assertLessThan( $all, count( Build_With_AI::examples( 'troubleshoot' ) ) );

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );
		$this->assertFalse( Build_With_AI::can_offer( 'troubleshoot' ) );
		$this->assertFalse( Build_With_AI::can_offer( 'strategy' ) );
	}

	/**
	 * The guides send the assistant to abilities by name, so they have to be real
	 */
	public function test_the_guides_only_name_abilities_that_exist() {

		if ( ! function_exists( 'wp_get_ability' ) ) {
			$this->markTestSkipped( 'Needs WordPress 6.9 for the Abilities API.' );
		}

		foreach ( [ 'strategy', 'troubleshoot' ] as $topic ) {

			$markdown = file_get_contents( Get_Authoring_Guide::guides()[ $topic ]['file'] );

			preg_match_all( '/`([a-z]+(?:-[a-z]+)+)`/', $markdown, $matches );

			$this->assertNotEmpty( $matches[1], $topic );

			foreach ( array_unique( $matches[1] ) as $name ) {
				$this->assertNotNull( wp_get_ability( "groundhogg/$name" ), "$topic names $name" );
			}
		}
	}

	public function test_the_other_guides_point_to_it() {

		foreach ( [ 'email', 'flow', 'setup', 'strategy', 'troubleshoot' ] as $topic ) {
			$this->assertStringContainsString( 'the `assistant` guide', Get_Authoring_Guide::content( $topic ), $topic );
		}
	}
}
