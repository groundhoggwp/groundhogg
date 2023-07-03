<?php

namespace Groundhogg\Admin\Welcome;

use Groundhogg\Admin\Admin_Page;
use function Groundhogg\admin_page_url;
use function Groundhogg\dashicon;
use function Groundhogg\files;
use function Groundhogg\get_db;
use function Groundhogg\groundhogg_logo;
use function Groundhogg\has_premium_features;
use function Groundhogg\html;
use function Groundhogg\is_pro_features_active;
use function Groundhogg\is_white_labeled;
use Groundhogg\License_Manager;
use Groundhogg\Plugin;
use function Groundhogg\qualifies_for_review_your_funnel;
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
				'data:image/svg+xml;base64,' . base64_encode( '
<svg viewBox="589.123 72.194 300.905 339.498" xmlns="http://www.w3.org/2000/svg">
  <g>
    <path d="M 639.14 311.679 C 638.562 310.335 637.823 309.065 636.94 307.899 C 636.94 307.899 626.86 314.199 617.72 300.659 C 608.59 287.109 611.42 243.329 611.42 243.329 C 611.42 243.329 593.79 242.699 589.69 225.379 C 585.59 208.059 604.81 192.309 612.05 188.529 C 613.95 172.779 616.78 165.539 616.78 165.539 C 616.78 165.539 596.94 156.089 601.35 127.429 C 605.75 98.769 637.88 83.649 659.61 100.029 C 705.91 66.009 765.44 63.809 808.59 89.949 C 822.45 80.499 841.97 78.299 858.35 96.569 C 874.73 114.829 863.39 137.819 856.78 143.489 C 862.13 151.369 865.28 161.759 865.28 161.759 C 865.28 161.759 883.54 171.209 888.9 186.329 C 894.25 201.449 878.82 209.629 878.82 209.629 C 878.82 209.629 888.09 234.589 887.01 255.929 C 885.75 280.819 876.61 289.629 868.74 288.689 C 865.78 298.758 860.738 308.093 853.94 316.089 C 860.937 323.422 867.385 335.242 871.161 342.895 L 854.546 352.489 L 862.557 347.863 C 857.771 336.014 850.485 325.283 841.15 316.429 C 852.307 305.784 860.249 292.219 864.07 277.279 C 872.82 282.939 883.63 265.699 876.68 231.719 C 873.98 218.499 871.36 209.419 869.16 203.249 C 874.39 200.859 877.56 198.989 878.49 197.989 C 882.61 193.619 883.38 182.289 862.01 169.669 C 860.74 168.899 859.28 168.099 857.64 167.269 C 854.639 158.341 850.85 149.697 846.32 141.439 C 851.42 137.329 861.12 127.429 857.7 113.559 C 852.93 94.189 831.85 87.659 820.83 92.779 C 816.722 94.589 813.205 97.517 810.68 101.229 C 789.31 86.449 765.52 81.489 745.34 81.389 C 715.02 81.239 682.66 91.679 660.59 109.989 C 652.31 102.909 641.36 100.919 630.47 104.939 C 612.7 111.499 603.44 138.529 617.34 153.589 C 620.314 156.834 624.145 159.174 628.39 160.339 C 623.47 174.739 622.11 186.299 621.73 191.879 C 596.39 209.679 595.56 223.379 603.83 230.179 C 607.31 233.049 611.74 234.739 621.14 235.739 C 620.2 246.689 619.55 260.949 620.55 274.709 C 622.61 302.769 636.25 300.189 640.63 293.239 C 645.488 300.071 650.957 306.448 656.97 312.289 C 642.525 319.964 629.409 329.851 618.084 341.563 L 625.573 345.888 L 608.565 336.067 C 616.074 329.018 630.611 315.904 639.14 311.679 Z M 757.43 408.569 C 750.222 412.733 741.338 412.733 734.13 408.569 L 672.802 373.158 C 674.841 366.889 676.999 360.5 678.47 356.819 C 681.027 350.436 673.503 354.135 663.867 367.998 L 671.142 372.199 L 629.543 348.18 C 637.91 340.343 650.793 330.323 668.71 321.809 C 684 331.959 693.35 329.329 690.31 327.479 C 667.35 313.439 656.85 295.559 645.26 267.759 C 634.71 286.289 632.91 271.619 634.45 247.419 C 635.52 230.819 637.05 219.439 637.92 213.919 C 650.53 208.6 663.446 204.039 676.6 200.259 C 676.43 201.869 676.71 203.539 678.47 203.919 C 680.98 204.459 681.87 200.889 687.62 197.289 C 695.81 195.212 704.068 193.411 712.38 191.889 C 719.17 192.879 722.7 195.939 724.04 195.939 C 725.69 195.939 726.18 192.779 724.04 189.929 C 734.61 188.319 745.92 187.009 758.02 186.159 C 769.36 185.339 779.67 184.949 789.02 184.859 C 788.38 186.139 788.04 187.659 789.94 187.959 C 792.05 188.289 795.92 186.449 800.11 184.919 C 808.207 185.06 816.296 185.507 824.36 186.259 C 826.109 186.874 827.775 187.703 829.32 188.729 C 832.25 190.879 836.2 190.369 835.15 187.529 C 840.95 188.339 845.91 189.279 850.1 190.239 C 852.48 193.989 857.98 204.079 863.04 223.739 C 869.74 249.739 865.36 260.799 863.04 261.839 C 860.73 262.869 854.54 255.909 854.54 255.909 C 854.54 255.909 854.03 277.279 837.04 302.509 C 820.06 327.739 809.76 328.509 815.16 330.819 C 817.18 331.689 823.14 329.789 830.33 325.089 C 834.307 329.125 843.387 339.26 851.118 354.469 Z M 821.3 230.009 C 817.952 233.016 813.61 234.676 809.11 234.669 C 795.05 234.669 786.25 219.449 793.29 207.269 C 800.32 195.079 817.9 195.079 824.94 207.269 C 825.3 207.882 825.62 208.516 825.9 209.169 C 820.021 203.341 810.219 204.65 806.075 211.817 C 801.208 220.233 807.277 230.762 817 230.769 C 818.467 230.77 819.922 230.513 821.3 230.009 Z M 704.09 200.299 C 683.82 200.299 671.16 222.249 681.29 239.799 C 686.566 249.022 696.896 254.139 707.43 252.749 C 705.25 253.969 703.31 255.009 702.47 255.529 C 701.62 256.049 713.02 256.089 723.06 244.919 C 724.533 243.371 725.815 241.652 726.88 239.799 C 729.2 235.789 730.41 231.249 730.41 226.629 C 730.416 212.089 718.63 200.299 704.09 200.299 Z M 721.15 217.729 C 720.625 217.254 720.064 216.821 719.472 216.434 C 710.79 210.763 699.226 216.618 698.656 226.971 C 698.087 237.325 708.939 244.412 718.19 239.729 C 714.67 243.509 709.65 245.879 704.09 245.879 C 689.27 245.879 680.02 229.839 687.42 217.009 C 694.83 204.179 713.35 204.179 720.75 217.009 Z M 809.35 241.499 C 818.246 241.464 826.445 236.678 830.85 228.949 C 830.85 228.948 830.851 228.947 830.851 228.947 C 840.511 212.212 828.432 191.297 809.11 191.299 C 789.79 191.299 777.72 212.219 787.38 228.949 C 788.893 231.589 790.872 233.934 793.22 235.869 C 798.64 241.099 809.17 244.029 813.93 243.489 C 815.53 243.299 812.17 242.579 809.35 241.499 Z M 658.85 140.839 C 657.065 143.691 655.628 146.746 654.57 149.939 C 648.35 150.959 632.76 152.699 627.38 142.579 C 621.01 130.619 632.2 110.729 646.49 114.979 C 658.22 118.459 663.71 129.239 665.29 132.979 C 662.789 135.291 660.622 137.94 658.85 140.849 Z M 672.09 128.359 C 671.246 125.589 670.137 122.907 668.78 120.349 C 671.974 117.487 675.366 114.854 678.93 112.469 C 708.85 92.389 769.28 86.209 802.49 109.959 C 826.11 126.849 837.68 149.679 842.2 160.749 C 827.87 155.589 808.66 150.649 787.2 148.229 C 794.915 126.996 779.191 104.55 756.6 104.549 C 733.05 104.549 717.79 128.509 726.43 149.409 C 708.09 152.339 680.41 159.089 640.89 180.239 C 639.635 180.914 638.385 181.597 637.14 182.289 C 638.364 175.021 640.177 167.864 642.56 160.889 C 646.126 160.317 649.62 159.362 652.98 158.039 C 652.76 162.219 653.73 164.399 654.21 163.239 C 656.667 156.174 659.975 149.435 664.06 143.169 C 670.97 132.079 678.92 127.909 677.57 127.139 C 676.99 126.809 674.89 127.059 672.09 128.359 Z M 833.44 102.229 C 843.7 103.329 854.91 121.629 840 131.129 C 834.212 122.519 827.294 114.726 819.43 107.959 C 821.68 105.299 826.25 101.469 833.44 102.239 Z M 866.87 188.079 C 833.64 177.719 773.08 171.479 703.96 186.419 C 648 198.499 621.58 211.919 612.06 217.719 C 612.99 208.539 634.95 193.709 662.26 179.979 C 687.72 167.179 713.06 161.379 732.22 158.739 C 745.44 173.799 769.69 173.309 782.19 157.269 C 805.34 159.229 831.18 164.919 851.98 175.599 C 860.96 180.209 865.94 184.279 866.87 188.079 Z M 756.6 159.899 C 739.07 159.899 728.1 140.919 736.87 125.719 C 745.64 110.539 767.56 110.539 776.33 125.719 C 778.33 129.189 779.38 133.119 779.38 137.119 C 779.38 149.7 769.181 159.899 756.6 159.899 Z M 806.41 270.839 C 803.32 272.129 788.39 275.989 782.99 275.989 C 777.58 275.989 768.83 270.069 764.71 270.069 C 760.59 270.069 749.27 281.769 736.39 282.169 C 729.538 282.274 722.702 281.494 716.05 279.849 C 713.75 279.269 704.99 277.789 710.65 284.739 C 730.14 308.649 744.64 323.359 763.94 321.809 C 783.24 320.269 794.82 300.449 801.52 288.089 C 808.21 275.739 809.51 269.559 806.41 270.839 Z M 772.57 296.269 C 771.4 297.039 760.01 298.199 758.27 297.429 C 756.54 296.649 753.64 284.299 753.64 284.299 C 753.64 284.299 763.49 277.739 765.8 277.349 C 768.12 276.959 771.6 279.669 776.8 281.209 C 776.42 288.539 773.72 295.489 772.57 296.269 Z M 814.39 366.609 C 815.408 368.506 816.658 370.992 817.945 373.624 L 823.908 370.181 C 814.451 359.441 811.853 361.864 814.39 366.599 Z M 776.69 253.619 C 783.95 250.799 786.33 246.879 786.33 243.559 C 786.33 238.659 779.33 233.299 760.85 235.059 C 744.63 236.609 734.59 243.039 735.62 248.709 C 736.35 252.739 740.61 256.119 749.61 256.989 C 752.44 261.449 757.33 265.449 764.71 264.149 C 771.27 262.989 775.23 259.079 776.69 253.619 Z" fill="#fff"/>
  </g>
</svg>' ), 3.1 );

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
		wp_enqueue_style( 'groundhogg-admin-element' );
		wp_enqueue_script( 'groundhogg-admin-data' );
		wp_enqueue_script( 'groundhogg-admin-element' );
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
	 * Shows the add for review your funnel
	 * Only shows if the conditions for viewing are met
	 *
	 * @return void
	 */
	public function promote_review_your_funnel() {

		if ( ! qualifies_for_review_your_funnel() ) {
			return;
		}

		?>
		<a href="<?php echo admin_page_url( 'gh_guided_setup', [], 'funnel-review' ) ?>">
			<img style="border-radius: 5px" alt="review your funnel offer"
			     src="<?php echo GROUNDHOGG_ASSETS_URL . 'images/review-your-funnel.png' ?>">
		</a>
		<?php


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
				<div class="display-flex column gap-20">
					<div class="gh-panel" id="ghmenu">
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
					<?php include __DIR__ . '/checklist.php' ?>
					<?php $this->promote_review_your_funnel() ?>
					<div class="gh-panel">
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
					<div class="display-flex gap-20">
						<div class="display-flex column gap-20">

							<!-- Import your list -->
							<div class="gh-panel">
								<?php

								echo html()->e( 'img', [
									'src'         => GROUNDHOGG_ASSETS_URL . 'images/welcome/import-your-contact-list-with-groundhogg.png',
									'data-yt-src' => 'https://www.youtube.com/embed/BmTmVAoWSb0',
									'class'       => 'show-video'
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
							</div>
							<!-- Create a funnel -->
							<div class="gh-panel">
								<?php

								echo html()->e( 'img', [
									'src'         => GROUNDHOGG_ASSETS_URL . 'images/welcome/create-your-first-funnel-with-groundhogg.png',
									'data-yt-src' => 'https://www.youtube.com/embed/W1dwQrqEPVw',
									'class'       => 'show-video'
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
							</div>
						</div>
						<div class="display-flex column gap-20">

							<!-- Send a Broadcast -->
							<div class="gh-panel">
								<?php

								echo html()->e( 'img', [
									'src'         => GROUNDHOGG_ASSETS_URL . 'images/welcome/send-your-first-broadcast-with-groundhogg.png',
									'data-yt-src' => 'https://www.youtube.com/embed/bwIbcsEG7Kg',
									'class'       => 'show-video'
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
							</div>

							<!-- Configure CRON -->
							<div class="gh-panel">
								<?php

								echo html()->e( 'img', [
									'src'         => GROUNDHOGG_ASSETS_URL . 'images/welcome/correctly-configure-wp-cron-for-groundhogg.png',
									'data-yt-src' => 'https://www.youtube.com/embed/1-csY3W-WP0',
									'class'       => 'show-video'
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
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<script>
          (($) => {
            $('.gh-panel button.toggle-indicator').on('click', e => {
              let $el = $(e.target).closest('.gh-panel')
              $el.toggleClass('closed')

              if ($el.hasClass('closed')) {
                Groundhogg.stores.options.patch({
                  gh_hide_groundhogg_quickstart: true,
                })
              } else {
                Groundhogg.stores.options.delete([
                  'gh_hide_groundhogg_quickstart',
                ])
              }
            })

            $('.show-video').on('click', e => {
              let $img = $(e.currentTarget)
              Groundhogg.element.modal({
                content: `<iframe width="800" height="450" src="${$img.data('yt-src')}"
                                            frameborder="0"
                                            allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
                                            allowfullscreen></iframe>`,
              })

            })
          })(jQuery)
		</script>
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
