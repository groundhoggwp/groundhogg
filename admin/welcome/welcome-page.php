<?php

namespace Groundhogg\Admin\Welcome;

use Groundhogg\Admin\Admin_Page;
use function Groundhogg\admin_page_url;
use function Groundhogg\dashicon;
use function Groundhogg\files;
use function Groundhogg\get_db;
use function Groundhogg\groundhogg_logo;
use function Groundhogg\html;
use function Groundhogg\is_white_labeled;
use Groundhogg\License_Manager;
use Groundhogg\Plugin;
use function Groundhogg\white_labeled_name;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Show a welcome screen which will help users find articles and extensions that will suit their needs.
 *
 * Class Page
 *
 * @package Groundhogg\Admin\Welcome
 */
class Welcome_Page extends Admin_Page {
	// UNUSED FUNCTIONS
	public function help() {
	}

	public function screen_options() {
	}

	protected function add_ajax_actions() {
	}

	/**
	 * Get the menu order between 1 - 99
	 *
	 * @return int
	 */
	public function get_priority() {
		return 1;
	}

	/**
	 * Get the page slug
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'groundhogg';
	}

	/**
	 * Get the menu name
	 *
	 * @return string
	 */
	public function get_name() {
		return apply_filters( 'groundhogg/admin/welcome/name', 'Groundhogg' );
	}

	/**
	 * The required minimum capability required to load the page
	 *
	 * @return string
	 */
	public function get_cap() {
		return 'view_contacts';
	}

	/**
	 * Get the item type for this page
	 *
	 * @return mixed
	 */
	public function get_item_type() {
		return null;
	}

	/**
	 * Adds additional actions.
	 *
	 * @return void
	 */
	protected function add_additional_actions() {
	}

	/**
	 * Add the page todo
	 */
	public function register() {

		if ( is_white_labeled() ) {
			$name = white_labeled_name();
		} else {
			$name = 'Groundhogg';
		}

		$page = add_menu_page(
			'Groundhogg',
			$name,
			'view_contacts',
			'groundhogg',
			[ $this, 'page' ],
			is_white_labeled() ? 'dashicons-email-alt' :
				'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="11.4 13.2 212.1 237.9">
  <path fill="#FFF" d="M 22.7 64.4 L 106.1 16.2 C 113.1 12.2 121.8 12.2 128.8 16.2 L 212.2 64.4 C 219.2 68.4 223.5 75.9 223.5 84 L 223.5 180.3 C 223.5 188.4 219.2 195.9 212.2 199.9 L 128.8 248.1 C 121.8 252.1 113.1 252.1 106.1 248.1 L 22.7 200 C 15.7 196 11.4 188.5 11.4 180.4 L 11.4 84 C 11.369 75.91 15.684 68.427 22.7 64.4 Z M 183.5 126 L 117.5 126 L 117.5 149.3 L 143.1 149.3 C 138.964 155.035 133.014 159.206 126.212 161.138 C 102.96 167.743 81.278 146.701 87.184 123.262 C 93.089 99.822 122.154 91.566 139.5 108.4 L 164 84.1 C 142.84 63.066 110.212 58.814 84.368 73.724 C 40.293 99.152 40.273 162.757 84.332 188.213 C 128.391 213.669 183.484 181.884 183.5 131 Z" />
</svg>' ),
			2

		);

		$sub_page = add_submenu_page(
			'groundhogg',
			_x( 'Welcome', 'page_title', 'groundhogg' ),
			_x( 'Welcome', 'page_title', 'groundhogg' ),
			'view_contacts',
			'groundhogg',
			array( $this, 'page' )
		);

		$this->screen_id = $page;

		/* White label compat */
		if ( is_white_labeled() ) {
			remove_submenu_page( 'groundhogg', 'groundhogg' );
		}

		add_action( "load-" . $page, array( $this, 'help' ) );
	}

	/* Enque JS or CSS */
	public function scripts() {
		wp_enqueue_style( 'groundhogg-admin' );
		wp_enqueue_style( 'groundhogg-admin-welcome' );
	}

	/**
	 * Display the title and dependent action include the appropriate page content
	 */
	public function page() {

		do_action( "groundhogg/admin/{$this->get_slug()}/before" );

		?>
		<div class="wrap">
			<?php

			if ( method_exists( $this, $this->get_current_action() ) ) {
				call_user_func( [ $this, $this->get_current_action() ] );
			} else if ( has_action( "groundhogg/admin/{$this->get_slug()}/display/{$this->get_current_action()}" ) ) {
				do_action( "groundhogg/admin/{$this->get_slug()}/display/{$this->get_current_action()}", $this );
			} else {
				call_user_func( [ $this, 'view' ] );
			}

			?>
		</div>
		<?php

		do_action( "groundhogg/admin/{$this->get_slug()}/after" );
	}


	/**
	 * The main output
	 */
	public function view() {
		?>

		<div id="welcome-page" class="welcome-page">
			<div id="poststuff">
				<div class="welcome-header">
					<h1><?php echo sprintf( __( 'Welcome to %s', 'groundhogg' ), groundhogg_logo( 'black', 300, false ) ); ?></h1>
				</div>
				<?php $this->notices(); ?>
				<hr class="wp-header-end">
				<div class="col">
					<div class="postbox" id="ghmenu">
						<div class="inside" style="padding: 0;margin: 0">
							<ul>
								<?php

								$links = [
									[
										'icon'    => 'admin-site',
										'display' => __( 'Groundhogg.io' ),
										'url'     => 'https://www.groundhogg.io'
									],
									[
										'icon'    => 'media-document',
										'display' => __( 'Documentation' ),
										'url'     => 'https://help.groundhogg.io'
									],
									[
										'icon'    => 'store',
										'display' => __( 'Store' ),
										'url'     => 'https://www.groundhogg.io/downloads/'
									],
									[
										'icon'    => 'welcome-learn-more',
										'display' => __( 'Courses' ),
										'url'     => 'https://academy.groundhogg.io/'
									],
									[
										'icon'    => 'sos',
										'display' => __( 'Support Group' ),
										'url'     => 'https://www.groundhogg.io/fb/'
									],
									[
										'icon'    => 'admin-users',
										'display' => __( 'My Account' ),
										'url'     => 'https://www.groundhogg.io/account/'
									],
									[
										'icon'    => 'location-alt',
										'display' => __( 'Find a Partner' ),
										'url'     => 'https://www.groundhogg.io/find-a-partner/'
									],
								];

								foreach ( $links as $link ) {

									echo html()->e( 'li', [], [
										html()->e( 'a', [
											'href'   => add_query_arg( [
												'utm_source'   => get_bloginfo(),
												'utm_medium'   => 'welcome-page',
												'utm_campaign' => 'admin-links',
												'utm_content'  => strtolower( $link['display'] ),
											], $link['url'] ),
											'target' => '_blank'
										], [
											dashicon( $link['icon'] ),
											'&nbsp;',
											$link['display']
										] )
									] );

								}

								?>
							</ul>
						</div>
					</div>
				</div>
				<?php include __DIR__ . '/checklist.php' ?>
				<div class="col">
					<div class="postbox">
						<?php

						echo html()->e( 'a', [
							'href'   => add_query_arg( [
								'utm_source'   => get_bloginfo(),
								'utm_medium'   => 'welcome-page',
								'utm_campaign' => 'quickstart',
								'utm_content'  => 'image',
							], 'https://academy.groundhogg.io/course/groundhogg-quickstart/' )
							,
							'target' => '_blank'
						], html()->e( 'img', [
							'src' => GROUNDHOGG_ASSETS_URL . 'images/welcome/quickstart-course-welcome-screen.png',
						] ) );

						echo html()->e( 'a', [
							'target' => '_blank',
							'class'  => 'button big-button',
							'href'   => add_query_arg( [
								'utm_source'   => get_bloginfo(),
								'utm_medium'   => 'welcome-page',
								'utm_campaign' => 'quickstart',
								'utm_content'  => 'button',
							], 'https://academy.groundhogg.io/course/groundhogg-quickstart/' ),
						], __( 'Take The Quickstart Course!', 'groundhogg' ) );

						?>
					</div>
				</div>

				<div class="left-col col">

					<!-- Import your list -->
					<div class="postbox">
						<?php

						echo html()->modal_link( [
							'title'              => __( 'Import your list!' ),
							'text'               => html()->e( 'img', [
								'src' => GROUNDHOGG_ASSETS_URL . 'images/welcome/import-your-contact-list-with-groundhogg.png',
							] ),
							'footer_button_text' => __( 'Close' ),
							'source'             => 'import-list-video',
							'class'              => 'img-link no-padding',
							'height'             => 555,
							'width'              => 800,
							'footer'             => 'true',
							'preventSave'        => 'true',
						] );

						echo html()->e( 'a', [
							'class' => 'button big-button',
							'href'  => admin_page_url( 'gh_tools', [ 'tab' => 'import', 'action' => 'add' ] )
						], __( 'Import your Contact List!', 'groundhogg' ) );

						echo html()->e( 'a', [
							'class'  => 'guide-link',
							'href'   => 'https://help.groundhogg.io/article/14-how-do-i-import-my-list',
							'target' => '_blank'
						], __( 'Read the full guide', 'groundhogg' ) );

						?>
						<div class="hidden" id="import-list-video">
							<iframe width="800" height="450" src="https://www.youtube.com/embed/BmTmVAoWSb0"
							        frameborder="0"
							        allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
							        allowfullscreen></iframe>
						</div>
					</div>

					<!-- Create a funnel -->
					<div class="postbox">
						<?php

						echo html()->modal_link( [
							'title'              => __( 'Create your first funnel!', 'groundhogg' ),
							'text'               => html()->e( 'img', [
								'src' => GROUNDHOGG_ASSETS_URL . 'images/welcome/create-your-first-funnel-with-groundhogg.png'
							] ),
							'footer_button_text' => __( 'Close' ),
							'source'             => 'create-your-first-funnel-video',
							'class'              => 'img-link no-padding',
							'height'             => 555,
							'width'              => 800,
							'footer'             => 'true',
							'preventSave'        => 'true',
						] );

						echo html()->e( 'a', [
							'class' => 'button big-button',
							'href'  => admin_page_url( 'gh_funnels', [ 'action' => 'add' ] )
						], __( 'Create your first Funnel!', 'groundhogg' ) );

						echo html()->e( 'a', [
							'class'  => 'guide-link',
							'href'   => 'https://help.groundhogg.io/article/112-how-to-setup-a-lead-magnet-download-funnel',
							'target' => '_blank'
						], __( 'Read the full guide', 'groundhogg' ) );

						?>
						<div class="hidden" id="create-your-first-funnel-video">
							<iframe width="800" height="450" src="https://www.youtube.com/embed/W1dwQrqEPVw"
							        frameborder="0"
							        allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
							        allowfullscreen></iframe>
						</div>
					</div>
				</div>
				<div class="right-col col">

					<!-- Send a Broadcast -->
					<div class="postbox">
						<?php

						echo html()->modal_link( [
							'title'              => __( 'Send your first broadcast!', 'groundhogg' ),
							'text'               => html()->e( 'img', [
								'src' => GROUNDHOGG_ASSETS_URL . 'images/welcome/send-your-first-broadcast-with-groundhogg.png'
							] ),
							'footer_button_text' => __( 'Close' ),
							'source'             => 'send-your-first-broadcast-video',
							'class'              => 'img-link no-padding',
							'height'             => 555,
							'width'              => 800,
							'footer'             => 'true',
							'preventSave'        => 'true',
						] );

						echo html()->e( 'a', [
							'class' => 'button big-button',
							'href'  => admin_page_url( 'gh_emails', [ 'action' => 'add' ] )
						], __( 'Send your first Broadcast!' ) ) ?>

						<?php echo html()->e( 'a', [
							'class'  => 'guide-link',
							'href'   => 'https://help.groundhogg.io/article/86-how-to-schedule-a-broadcast',
							'target' => '_blank'
						], __( 'Read the full guide', 'groundhogg' ) ); ?>

						<div class="hidden" id="send-your-first-broadcast-video">
							<iframe width="800" height="450" src="https://www.youtube.com/embed/bwIbcsEG7Kg"
							        frameborder="0"
							        allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
							        allowfullscreen></iframe>
						</div>
					</div>

					<!-- Configure CRON -->
					<div class="postbox">
						<?php
						echo html()->modal_link( [
							'title'              => __( 'Configure WP-Cron', 'groundhogg' ),
							'text'               => html()->e( 'img', [
								'src' => GROUNDHOGG_ASSETS_URL . 'images/welcome/correctly-configure-wp-cron-for-groundhogg.png'
							] ),
							'footer_button_text' => __( 'Close' ),
							'source'             => 'configure-wp-cron',
							'class'              => 'img-link no-padding',
							'height'             => 555,
							'width'              => 800,
							'footer'             => 'true',
							'preventSave'        => 'true',
						] );

						echo html()->e( 'a', [
							'class' => 'button big-button',
							'href'  => admin_page_url( 'gh_tools', [ 'tab' => 'cron' ] )
						], __( 'Configure WP-Cron!' ) );

						echo html()->e( 'a', [
							'class'  => 'guide-link',
							'href'   => 'https://help.groundhogg.io/article/45-how-to-disable-builtin-wp-cron',
							'target' => '_blank'
						], __( 'Read the full guide', 'groundhogg' ) ); ?>
						<div class="hidden" id="configure-wp-cron">
							<iframe width="800" height="450" src="https://www.youtube.com/embed/1-csY3W-WP0"
							        frameborder="0"
							        allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
							        allowfullscreen></iframe>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Hides the quickstart check list
	 */
	public function process_hide_checklist() {
		update_user_option( get_current_user_id(), 'gh_hide_groundhogg_quickstart', true );
	}

	/**
	 * Shows the quickstart check list
	 */
	public function process_show_checklist() {
		delete_user_option( get_current_user_id(), 'gh_hide_groundhogg_quickstart' );
	}

}
