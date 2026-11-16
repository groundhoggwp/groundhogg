<?php

use Groundhogg\Plugin;

/**
 * The email service check: email sent by the web server, or through a third-party SMTP plugin, which sends it but
 * doesn't report bounces or spam complaints, so those contacts keep getting email.
 */
class Site_Health_Email_Service_Tests extends GH_UnitTestCase {

	protected $plugin_dir;

	public function set_up() {
		parent::set_up();

		update_option( 'gh_marketing_email_service', 'wp_mail' );

		// a stand-in for an SMTP plugin, which sets up PHPMailer from its own folder
		$this->plugin_dir = WP_PLUGIN_DIR . '/fake-smtp-mailer';

		if ( ! is_dir( $this->plugin_dir ) ) {
			mkdir( $this->plugin_dir, 0777, true );
		}

		file_put_contents( $this->plugin_dir . '/fake-smtp-mailer.php', "<?php\n/**\n * Plugin Name: Fake SMTP Mailer\n */\nif ( ! function_exists( 'fake_smtp_mailer_init' ) ) {\n\tfunction fake_smtp_mailer_init( \$phpmailer ) {}\n}\n" );

		wp_cache_delete( 'plugins', 'plugins' );
	}

	public function tear_down() {
		remove_action( 'phpmailer_init', 'fake_smtp_mailer_init' );

		@unlink( $this->plugin_dir . '/fake-smtp-mailer.php' );
		@rmdir( $this->plugin_dir );
		wp_cache_delete( 'plugins', 'plugins' );

		parent::tear_down();
	}

	public function test_email_from_the_web_server_is_recommended_against() {

		$result = Plugin::instance()->site_health->test_email_service();

		$this->assertSame( 'recommended', $result['status'] );
		$this->assertSame( 'Email is sent by the web server', $result['label'] );
	}

	public function test_a_third_party_smtp_plugin_is_flagged_for_not_reporting_bounces() {

		require_once $this->plugin_dir . '/fake-smtp-mailer.php';
		add_action( 'phpmailer_init', 'fake_smtp_mailer_init' );

		$health = Plugin::instance()->site_health;

		$this->assertSame( 'fake-smtp-mailer', $health->wp_mail_plugin() );

		$result = $health->test_email_service();

		$this->assertSame( 'recommended', $result['status'] );
		$this->assertSame( 'Bounces and spam complaints aren\'t reported', $result['label'] );
		$this->assertStringContainsString( 'Fake SMTP Mailer', $result['description'] );
		$this->assertStringContainsString( 'bounces', $result['description'] );
	}

	public function test_an_email_service_of_ours_is_good() {

		require_once $this->plugin_dir . '/fake-smtp-mailer.php';
		add_action( 'phpmailer_init', 'fake_smtp_mailer_init' );

		update_option( 'gh_marketing_email_service', 'log_only' );

		$this->assertSame( 'good', Plugin::instance()->site_health->test_email_service()['status'] );
	}
}
