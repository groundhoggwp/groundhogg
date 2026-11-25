<?php

use Groundhogg\Plugin;
use Groundhogg\Steps\Premium\Actions\AI_Agent;
use function Groundhogg\is_pro_features_active;

/**
 * The AI Agent step without Advanced Features: it's in the flow editor to find, and does nothing but say it's a paid
 * feature
 */
class Premium_AI_Agent_Tests extends GH_UnitTestCase {

	public function set_up(): void {
		parent::set_up();

		if ( is_pro_features_active() ) {
			$this->markTestSkipped( 'Advanced Features is active, so its own AI Agent step is registered.' );
		}
	}

	public function test_it_is_offered_without_advanced_features() {

		$element = Plugin::instance()->step_manager->get_element( 'ai_agent' );

		$this->assertInstanceOf( AI_Agent::class, $element );
		$this->assertTrue( $element->is_premium() );
		$this->assertEquals( 'crm', $element->get_sub_group() );
		$this->assertFileExists( GROUNDHOGG_ASSETS_PATH . 'images/funnel-icons/crm/ai-agent.svg' );
	}

	public function test_running_it_says_it_is_a_paid_feature() {

		$result = ( new AI_Agent() )->run( null, null );

		$this->assertWPError( $result );
		$this->assertEquals( 'premium', $result->get_error_code() );
	}
}
