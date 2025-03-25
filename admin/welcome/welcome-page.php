<?php

namespace Groundhogg\Admin\Welcome;

use Groundhogg\Admin\Admin_Page;
use function Groundhogg\admin_page_url;
use function Groundhogg\db;
use function Groundhogg\files;
use function Groundhogg\get_db;
use function Groundhogg\gh_cron_installed;
use function Groundhogg\has_premium_features;
use function Groundhogg\is_event_queue_processing;
use function Groundhogg\is_option_enabled;
use function Groundhogg\is_white_labeled;
use function Groundhogg\notices;
use function Groundhogg\remote_post_json;
use function Groundhogg\verify_admin_ajax_nonce;
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
		add_action( 'wp_ajax_gh_get_checklist_items', [ $this, 'ajax_get_checklist_items' ] );
		add_action( 'wp_ajax_gh_get_recommendation_items', [ $this, 'ajax_get_recommendation_items' ] );
		add_action( 'wp_ajax_gh_get_news', [ $this, 'ajax_get_news' ] );
	}

	/**
	 * Get the news
	 *
	 * @return void
	 */
	public function ajax_get_news() {
		if ( ! verify_admin_ajax_nonce() ) {
			wp_send_json_error();
		}

		$json = remote_post_json( 'https://www.groundhogg.io/wp-json/wp/v2/posts', [], 'GET' );

		wp_send_json( $json );
	}

	public function ajax_get_checklist_items() {

		if ( ! verify_admin_ajax_nonce() ) {
			wp_send_json_error();
		}

		$checklist_items = [
			[
				'title'       => __( 'Complete the guided setup', 'groundhogg' ),
				'description' => __( "Configure your initial settings and discover potential opportunities.", 'groundhogg' ),
				'completed'   => is_option_enabled( 'gh_guided_setup_finished' ),
				'fix'         => admin_page_url( 'gh_guided_setup' ),
				'cap'         => is_white_labeled() ? 'do_not_allow' : 'manage_options'
			],
			[
				'title'       => __( 'Configure your cron jobs', 'groundhogg' ),
				'description' => __( 'This is an optional best practice and will improve the performance of your site.', 'groundhogg' ),
				'completed'   => gh_cron_installed() && is_event_queue_processing() && apply_filters( 'groundhogg/cron/verified', true ),
				'fix'         => admin_page_url( 'gh_tools', [ 'tab' => 'cron' ] ),
				'cap'         => 'manage_options'
			],
			[
				'title'       => __( 'Sync your users & contacts', 'groundhogg' ),
				'description' => __( "It looks like you have existing users in your site, let's sync them with your contacts so you can send them email.", 'groundhogg' ),
				'completed'   => count_users()['total_users'] <= get_db( 'contacts' )->count(),
				'fix'         => admin_page_url( 'gh_tools', [ 'tab' => 'misc' ] ),
				'cap'         => 'add_users'
			],
			[
				'title'       => __( 'Import your list of contacts', 'groundhogg' ),
				'description' => __( "Let's bring in all your contacts, upload a CSV and import them into Groundhogg.", 'groundhogg' ),
				'completed'   => count( files()->get_imports() ) > 0,
				'fix'         => admin_page_url( 'gh_tools', [ 'tab' => 'import', 'action' => 'add' ] ),
				'cap'         => 'import_contacts'
			],
			[
				'title'       => __( 'Send a broadcast to your subscribers!', 'groundhogg' ),
				'description' => __( "Let's make sure your subscribers can hear you. Send them a broadcast email and say hello!", 'groundhogg' ),
				'completed'   => get_db( 'broadcasts' )->count( [ 'status' => 'sent' ] ) > 0,
				'fix'         => admin_page_url( 'gh_broadcasts', [ 'action' => 'add' ] ),
				'cap'         => 'edit_emails'
			],
			[
				'title'       => __( 'Create a flow', 'groundhogg' ),
				'description' => __( "We're going to create a flow that will welcome new subscribers to the list. It will only take a few minutes.", 'groundhogg' ),
				'completed'   => get_db( 'funnels' )->count( [ 'status' => 'active' ] ) > 0,
				'fix'         => admin_page_url( 'gh_funnels', [ 'action' => 'add' ] ),
				'cap'         => 'edit_funnels'
			],
		];

		apply_filters( 'groundhogg/admin/checklist_items', $checklist_items );

		$checklist_items = array_filter( $checklist_items, function ( $item ) {
			return current_user_can( $item['cap'] );
		} );

		wp_send_json_success( [
			'items' => array_values( $checklist_items ),
		] );
	}

	public function ajax_get_recommendation_items() {

		if ( ! verify_admin_ajax_nonce() ) {
			wp_send_json_error();
		}

		$reports   = get_option( 'gh_custom_reports', [] );
		$signature = get_user_meta( get_current_user_id(), 'signature', true );

		// MailHawk is installed but not connected -> redirect to the mailhawk connect page
		if ( function_exists( 'mailhawk_is_connected' ) && ! mailhawk_is_connected() ):
			$smtp_fix_link = admin_page_url( 'mailhawk' );

		// The number of registered services is > 1, means that an integration is installed.
        elseif ( count( \Groundhogg_Email_Services::get() ) > 1 ):
			$smtp_fix_link = admin_page_url( 'gh_settings', [ 'tab' => 'email' ] );

		// No other service is currently in use.
		else:
			$smtp_fix_link = admin_page_url( 'gh_guided_setup', [ 'step' => '3' ] );

		endif;

		$checklist_items = [
			[
				'title'       => __( 'Get more leads with HollerBox', 'groundhogg' ),
				'description' => __( "Popups and lead generation made easy with our free HollerBox plugin.", 'groundhogg' ),
				'completed'   => defined( 'HOLLERBOX_VERSION' ),
				'fix'         => admin_url( 'plugin-install.php?s=hollerbox&tab=search&type=term' ),
				'cap'         => is_white_labeled() ? 'do_not_allow' : 'install_plugins'
			],
			[
				'title'       => __( 'Upgrade to premium for powerful features', 'groundhogg' ),
				'description' => __( 'Get a premium plan and activate more powerful features that will help you grow and scale.', 'groundhogg' ),
				'completed'   => has_premium_features(),
				'fix'         => 'https://groundhogg.io/pricing/?utm_source=plugin&utm_medium=checklist&utm_campaign=welcome&utm_content=fix',
				'cap'         => is_white_labeled() ? 'do_not_allow' : 'manage_options'
			],
			[
				'title'       => __( 'Integrate a verified SMTP service', 'groundhogg' ),
				'description' => __( "You need a proper SMTP service to ensure your email reaches the inbox. We recommend <a href='https://mailhawk.io'>MailHawk!</a>", 'groundhogg' ),
				'completed'   => \Groundhogg_Email_Services::get_marketing_service() !== 'wp_mail' || function_exists( 'mailhawk_mail' ),
				'fix'         => $smtp_fix_link,
				'cap'         => is_white_labeled() ? 'manage_gh_licenses' : 'install_plugins'
			],
			[
				'title'       => __( 'Leverage flow conversion tracking', 'groundhogg' ),
				'description' => __( "Know how your flows are performing by using conversion tracking!", 'groundhogg' ),
				'completed'   => db()->steps->count( [ 'is_conversion' => 1 ] ) > 0,
				'fix'         => admin_page_url( 'gh_funnels' ),
				'cap'         => 'edit_funnels'
			],
			[
				'title'       => __( 'Know more with custom reports', 'groundhogg' ),
				'description' => __( "Use custom reports to know your subscribers and customers better.", 'groundhogg' ),
				'completed'   => ! empty( $reports ),
				'fix'         => admin_page_url( 'gh_reporting', [ 'tab' => 'custom' ] ),
				'cap'         => 'view_reports'
			],
			[
				'title'       => __( 'Organize your marketing with campaigns', 'groundhogg' ),
				'description' => __( "Campaigns are an easy way to organize your marketing efforts and make analytics easier.", 'groundhogg' ),
				'completed'   => ! empty( $reports ),
				'fix'         => admin_page_url( 'gh_campaigns' ),
				'cap'         => 'manage_campaigns'
			],
			[
				'title'       => __( 'Add your email signature', 'groundhogg' ),
				'description' => __( "Adding an email signature to your profile will make it easier for subscribers to know who your are.", 'groundhogg' ),
				'completed'   => ! empty( $signature ),
				'fix'         => admin_url( 'profile.php' ) . '#groundhogg-options',
				'cap'         => 'send_emails'
			],
		];

		if ( has_premium_features() ) {

			$checklist_items[] = [
				'title'       => __( 'Install the Extension Manager', 'groundhogg' ),
				'description' => __( 'The extension manager makes is easier to install your premium add-ons and integrations.', 'groundhogg' ),
				'completed'   => defined( 'GROUNDHOGG_HELPER_VERSION' ),
				'fix'         => 'https://groundhogg.io/account/all-access-downloads/',
				'cap'         => is_white_labeled() ? 'manage_gh_licenses' : 'install_plugins',
				'more'        => ''
			];

            if ( defined( 'GROUNDHOGG_HELPER_VERSION' ) ) {
	            $checklist_items[] = [
		            'title'       => __( 'Install the Advanced Features add-on', 'groundhogg' ),
		            'description' => __( 'The Advanced Features addon includes a variety of tools and that improve Groundhogg.', 'groundhogg' ),
		            'completed'   => defined( 'GROUNDHOGG_PRO_VERSION' ),
		            'fix'         => admin_page_url( 'gh_extensions' ),
		            'cap'         => is_white_labeled() ? 'manage_gh_licenses' : 'install_plugins',
		            'more'        => ''
	            ];
            }


            if ( defined( 'GROUNDHOGG_PRO_VERSION' ) ){
	            $checklist_items[] = [
		            'title'       => __( 'Enable automatic one-click unsubscribe', 'groundhogg' ),
		            'description' => __( 'Improve deliver ability by allowing subscribers to opt-out from their inbox.', 'groundhogg' ),
		            'completed'   => is_option_enabled( 'gh_use_unsubscribe_me' ),
		            'fix'         => admin_page_url( 'gh_settings', [ 'tab' => 'email' ] ),
		            'cap'         => 'manage_options',
		            'more'        => ''
	            ];
            }
		}

		apply_filters( 'groundhogg/admin/recommendation_items', $checklist_items );

		$checklist_items = array_filter( $checklist_items, function ( $item ) {
			return current_user_can( $item['cap'] );
		} );

		wp_send_json_success( [
			'items' => array_values( $checklist_items ),
		] );
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
		add_action( 'in_admin_header', array( $this, 'prevent_notices' ) );
	}

	/**
	 * Add the page todo
	 */
	public function register() {

		$page = add_menu_page(
			'Groundhogg',
			white_labeled_name(),
			'view_contacts',
			'groundhogg',
			[ $this, 'page' ],
			is_white_labeled() ? 'dashicons-email-alt' :
				'data:image/svg+xml;base64,' . base64_encode( '
<svg viewBox="589.123 72.194 300.905 339.498" xmlns="http://www.w3.org/2000/svg">
  <g>
    <path d="M 639.14 311.679 C 638.562 310.335 637.823 309.065 636.94 307.899 C 636.94 307.899 626.86 314.199 617.72 300.659 C 608.59 287.109 611.42 243.329 611.42 243.329 C 611.42 243.329 593.79 242.699 589.69 225.379 C 585.59 208.059 604.81 192.309 612.05 188.529 C 613.95 172.779 616.78 165.539 616.78 165.539 C 616.78 165.539 596.94 156.089 601.35 127.429 C 605.75 98.769 637.88 83.649 659.61 100.029 C 705.91 66.009 765.44 63.809 808.59 89.949 C 822.45 80.499 841.97 78.299 858.35 96.569 C 874.73 114.829 863.39 137.819 856.78 143.489 C 862.13 151.369 865.28 161.759 865.28 161.759 C 865.28 161.759 883.54 171.209 888.9 186.329 C 894.25 201.449 878.82 209.629 878.82 209.629 C 878.82 209.629 888.09 234.589 887.01 255.929 C 885.75 280.819 876.61 289.629 868.74 288.689 C 865.78 298.758 860.738 308.093 853.94 316.089 C 860.937 323.422 867.385 335.242 871.161 342.895 L 854.546 352.489 L 862.557 347.863 C 857.771 336.014 850.485 325.283 841.15 316.429 C 852.307 305.784 860.249 292.219 864.07 277.279 C 872.82 282.939 883.63 265.699 876.68 231.719 C 873.98 218.499 871.36 209.419 869.16 203.249 C 874.39 200.859 877.56 198.989 878.49 197.989 C 882.61 193.619 883.38 182.289 862.01 169.669 C 860.74 168.899 859.28 168.099 857.64 167.269 C 854.639 158.341 850.85 149.697 846.32 141.439 C 851.42 137.329 861.12 127.429 857.7 113.559 C 852.93 94.189 831.85 87.659 820.83 92.779 C 816.722 94.589 813.205 97.517 810.68 101.229 C 789.31 86.449 765.52 81.489 745.34 81.389 C 715.02 81.239 682.66 91.679 660.59 109.989 C 652.31 102.909 641.36 100.919 630.47 104.939 C 612.7 111.499 603.44 138.529 617.34 153.589 C 620.314 156.834 624.145 159.174 628.39 160.339 C 623.47 174.739 622.11 186.299 621.73 191.879 C 596.39 209.679 595.56 223.379 603.83 230.179 C 607.31 233.049 611.74 234.739 621.14 235.739 C 620.2 246.689 619.55 260.949 620.55 274.709 C 622.61 302.769 636.25 300.189 640.63 293.239 C 645.488 300.071 650.957 306.448 656.97 312.289 C 642.525 319.964 629.409 329.851 618.084 341.563 L 625.573 345.888 L 608.565 336.067 C 616.074 329.018 630.611 315.904 639.14 311.679 Z M 757.43 408.569 C 750.222 412.733 741.338 412.733 734.13 408.569 L 672.802 373.158 C 674.841 366.889 676.999 360.5 678.47 356.819 C 681.027 350.436 673.503 354.135 663.867 367.998 L 671.142 372.199 L 629.543 348.18 C 637.91 340.343 650.793 330.323 668.71 321.809 C 684 331.959 693.35 329.329 690.31 327.479 C 667.35 313.439 656.85 295.559 645.26 267.759 C 634.71 286.289 632.91 271.619 634.45 247.419 C 635.52 230.819 637.05 219.439 637.92 213.919 C 650.53 208.6 663.446 204.039 676.6 200.259 C 676.43 201.869 676.71 203.539 678.47 203.919 C 680.98 204.459 681.87 200.889 687.62 197.289 C 695.81 195.212 704.068 193.411 712.38 191.889 C 719.17 192.879 722.7 195.939 724.04 195.939 C 725.69 195.939 726.18 192.779 724.04 189.929 C 734.61 188.319 745.92 187.009 758.02 186.159 C 769.36 185.339 779.67 184.949 789.02 184.859 C 788.38 186.139 788.04 187.659 789.94 187.959 C 792.05 188.289 795.92 186.449 800.11 184.919 C 808.207 185.06 816.296 185.507 824.36 186.259 C 826.109 186.874 827.775 187.703 829.32 188.729 C 832.25 190.879 836.2 190.369 835.15 187.529 C 840.95 188.339 845.91 189.279 850.1 190.239 C 852.48 193.989 857.98 204.079 863.04 223.739 C 869.74 249.739 865.36 260.799 863.04 261.839 C 860.73 262.869 854.54 255.909 854.54 255.909 C 854.54 255.909 854.03 277.279 837.04 302.509 C 820.06 327.739 809.76 328.509 815.16 330.819 C 817.18 331.689 823.14 329.789 830.33 325.089 C 834.307 329.125 843.387 339.26 851.118 354.469 Z M 821.3 230.009 C 817.952 233.016 813.61 234.676 809.11 234.669 C 795.05 234.669 786.25 219.449 793.29 207.269 C 800.32 195.079 817.9 195.079 824.94 207.269 C 825.3 207.882 825.62 208.516 825.9 209.169 C 820.021 203.341 810.219 204.65 806.075 211.817 C 801.208 220.233 807.277 230.762 817 230.769 C 818.467 230.77 819.922 230.513 821.3 230.009 Z M 704.09 200.299 C 683.82 200.299 671.16 222.249 681.29 239.799 C 686.566 249.022 696.896 254.139 707.43 252.749 C 705.25 253.969 703.31 255.009 702.47 255.529 C 701.62 256.049 713.02 256.089 723.06 244.919 C 724.533 243.371 725.815 241.652 726.88 239.799 C 729.2 235.789 730.41 231.249 730.41 226.629 C 730.416 212.089 718.63 200.299 704.09 200.299 Z M 721.15 217.729 C 720.625 217.254 720.064 216.821 719.472 216.434 C 710.79 210.763 699.226 216.618 698.656 226.971 C 698.087 237.325 708.939 244.412 718.19 239.729 C 714.67 243.509 709.65 245.879 704.09 245.879 C 689.27 245.879 680.02 229.839 687.42 217.009 C 694.83 204.179 713.35 204.179 720.75 217.009 Z M 809.35 241.499 C 818.246 241.464 826.445 236.678 830.85 228.949 C 830.85 228.948 830.851 228.947 830.851 228.947 C 840.511 212.212 828.432 191.297 809.11 191.299 C 789.79 191.299 777.72 212.219 787.38 228.949 C 788.893 231.589 790.872 233.934 793.22 235.869 C 798.64 241.099 809.17 244.029 813.93 243.489 C 815.53 243.299 812.17 242.579 809.35 241.499 Z M 658.85 140.839 C 657.065 143.691 655.628 146.746 654.57 149.939 C 648.35 150.959 632.76 152.699 627.38 142.579 C 621.01 130.619 632.2 110.729 646.49 114.979 C 658.22 118.459 663.71 129.239 665.29 132.979 C 662.789 135.291 660.622 137.94 658.85 140.849 Z M 672.09 128.359 C 671.246 125.589 670.137 122.907 668.78 120.349 C 671.974 117.487 675.366 114.854 678.93 112.469 C 708.85 92.389 769.28 86.209 802.49 109.959 C 826.11 126.849 837.68 149.679 842.2 160.749 C 827.87 155.589 808.66 150.649 787.2 148.229 C 794.915 126.996 779.191 104.55 756.6 104.549 C 733.05 104.549 717.79 128.509 726.43 149.409 C 708.09 152.339 680.41 159.089 640.89 180.239 C 639.635 180.914 638.385 181.597 637.14 182.289 C 638.364 175.021 640.177 167.864 642.56 160.889 C 646.126 160.317 649.62 159.362 652.98 158.039 C 652.76 162.219 653.73 164.399 654.21 163.239 C 656.667 156.174 659.975 149.435 664.06 143.169 C 670.97 132.079 678.92 127.909 677.57 127.139 C 676.99 126.809 674.89 127.059 672.09 128.359 Z M 833.44 102.229 C 843.7 103.329 854.91 121.629 840 131.129 C 834.212 122.519 827.294 114.726 819.43 107.959 C 821.68 105.299 826.25 101.469 833.44 102.239 Z M 866.87 188.079 C 833.64 177.719 773.08 171.479 703.96 186.419 C 648 198.499 621.58 211.919 612.06 217.719 C 612.99 208.539 634.95 193.709 662.26 179.979 C 687.72 167.179 713.06 161.379 732.22 158.739 C 745.44 173.799 769.69 173.309 782.19 157.269 C 805.34 159.229 831.18 164.919 851.98 175.599 C 860.96 180.209 865.94 184.279 866.87 188.079 Z M 756.6 159.899 C 739.07 159.899 728.1 140.919 736.87 125.719 C 745.64 110.539 767.56 110.539 776.33 125.719 C 778.33 129.189 779.38 133.119 779.38 137.119 C 779.38 149.7 769.181 159.899 756.6 159.899 Z M 806.41 270.839 C 803.32 272.129 788.39 275.989 782.99 275.989 C 777.58 275.989 768.83 270.069 764.71 270.069 C 760.59 270.069 749.27 281.769 736.39 282.169 C 729.538 282.274 722.702 281.494 716.05 279.849 C 713.75 279.269 704.99 277.789 710.65 284.739 C 730.14 308.649 744.64 323.359 763.94 321.809 C 783.24 320.269 794.82 300.449 801.52 288.089 C 808.21 275.739 809.51 269.559 806.41 270.839 Z M 772.57 296.269 C 771.4 297.039 760.01 298.199 758.27 297.429 C 756.54 296.649 753.64 284.299 753.64 284.299 C 753.64 284.299 763.49 277.739 765.8 277.349 C 768.12 276.959 771.6 279.669 776.8 281.209 C 776.42 288.539 773.72 295.489 772.57 296.269 Z M 814.39 366.609 C 815.408 368.506 816.658 370.992 817.945 373.624 L 823.908 370.181 C 814.451 359.441 811.853 361.864 814.39 366.599 Z M 776.69 253.619 C 783.95 250.799 786.33 246.879 786.33 243.559 C 786.33 238.659 779.33 233.299 760.85 235.059 C 744.63 236.609 734.59 243.039 735.62 248.709 C 736.35 252.739 740.61 256.119 749.61 256.989 C 752.44 261.449 757.33 265.449 764.71 264.149 C 771.27 262.989 775.23 259.079 776.69 253.619 Z" fill="#fff"/>
  </g>
</svg>' ), 2 );

		$sub_page = add_submenu_page(
			'groundhogg',
			_x( 'Dashboard', 'page_title', 'groundhogg' ),
			_x( 'Dashboard', 'page_title', 'groundhogg' ),
			'view_contacts',
			'groundhogg',
			array( $this, 'page' )
		);

		$this->screen_id = $page;

		add_action( "load-" . $page, array( $this, 'help' ) );
	}

	/* Enque JS or CSS */
	public function scripts() {

		wp_enqueue_style( 'groundhogg-admin' );
		wp_enqueue_style( 'groundhogg-admin-welcome' );
		wp_enqueue_style( 'groundhogg-admin-element' );
		wp_enqueue_style( 'groundhogg-admin-reporting' );

		wp_enqueue_script( 'groundhogg-admin-dashboard' );
		wp_enqueue_editor();


	}

	/**
	 * Display the title and dependent action include the appropriate page content
	 */
	public function page() {

		?>
        <div id="dashboard"></div>
		<?php
	}

	/**
	 * The main output
	 */
	public function view() {

	}
}
