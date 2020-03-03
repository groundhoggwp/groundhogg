<?php

namespace Groundhogg\Admin\Reports;

use Groundhogg\Admin\Reports\Views\Overview;
use Groundhogg\Admin\Tabbed_Admin_Page;
use Groundhogg\Contact_Query;
use Groundhogg\Reports;
use function Groundhogg\get_post_var;
use function Groundhogg\get_request_var;
use function Groundhogg\groundhogg_logo;
use function Groundhogg\is_white_labeled;
use function Groundhogg\white_labeled_name;
use Groundhogg\Plugin;

class Reports_Page extends Tabbed_Admin_Page {

	/**
	 * Add Ajax actions...
	 *
	 * @return mixed
	 */
	protected function add_ajax_actions() {
		add_action( 'wp_ajax_groundhogg_refresh_dashboard_reports', [ $this, 'refresh_report_data' ] );
	}

	/**
	 * Adds additional actions.
	 *
	 * @return mixed
	 */
	protected function add_additional_actions() {
		// TODO: Implement add_additional_actions() method.
	}

	/**
	 * Get the page slug
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'gh_reporting';
	}

	/**
	 * Get the menu name
	 *
	 * @return string
	 */
	public function get_name() {
		return 'Reporting';
	}

	/**
	 * The required minimum capability required to load the page
	 *
	 * @return string
	 */
	public function get_cap() {
		return 'view_reports';
	}

	public function get_priority() {
		return 2;
	}

	/**
	 * Get the item type for this page
	 *
	 * @return mixed
	 */
	public function get_item_type() {
		// TODO: Implement get_item_type() method.
	}

	/**
	 * Enqueue any scripts
	 */
	public function scripts() {
		wp_enqueue_style( 'groundhogg-admin-reporting' );
		wp_enqueue_style( 'groundhogg-admin-loader' );
		wp_enqueue_style( 'baremetrics-calendar' );
//	    wp_enqueue_script( 'moment-js' );
//		wp_enqueue_script( 'baremetrics-calendar' );
		wp_enqueue_script( 'groundhogg-admin-reporting' );

		wp_localize_script( 'groundhogg-admin-reporting', 'GroundhoggReporting', [
			'reports' => $this->get_reports_per_tab()
		] );
	}

	protected function get_reports_per_tab() {

		switch ( $this->get_current_tab() ) {

			case 'overview':

				$reports = [
					'chart_new_contacts',

					'total_new_contacts',
					'total_confirmed_contacts',
					'total_engaged_contacts',
					'total_unsubscribes',

					'total_emails_sent',
					'email_open_rate',
					'email_click_rate',
				];

				break;
			case 'contacts' :
				$reports = [
					'chart_new_contacts',
				];
				break;
			case 'email':
				$reports = [
					'chart_email_activity'
				];

				break;
            case 'funnels':
                $reports = [
	                'chart_funnel_breakdown'
                ];

		}

		return $reports;

	}

	/**
	 * Add any help items
	 *
	 * @return mixed
	 */
	public function help() {
		// TODO: Implement help() method.
	}

	/**
	 * array of [ 'name', 'slug' ]
	 *
	 * @return array[]
	 */
	protected function get_tabs() {

		return [
			[
				'name' => __( 'Overview', 'groundhogg' ),
				'slug' => 'overview'
			],
			[
				'name' => __( 'Contacts', 'groundhogg' ),
				'slug' => 'contacts'
			],
			[
				'name' => __( 'Email', 'groundhogg' ),
				'slug' => 'email'
			],
			[
				'name' => __( 'Funnels', 'groundhogg' ),
				'slug' => 'funnels'
			],

		];
	}

	protected function get_title_actions() {
		return [];
	}

	public function page() {

		do_action( "groundhogg/admin/{$this->get_slug()}", $this );
		do_action( "groundhogg/admin/{$this->get_slug()}/{$this->get_current_tab()}", $this );

		?>
        <div class="loader-wrap">
            <div class="gh-loader-overlay" style="display:none;"></div>
            <div class="gh-loader" style="display: none"></div>
        </div>
        <div class="wrap">
			<?php if ( ! is_white_labeled() ): ?>
                <h1 class="wp-heading-inline"><?php groundhogg_logo( 'black' ); ?></h1>
			<?php else: ?>
                <h1 class="wp-heading-inline"><?php echo esc_html( white_labeled_name() ); ?></h1>
			<?php endif; ?>
			<?php $this->do_title_actions(); ?>
			<?php $this->range_picker(); ?>
			<?php $this->notices(); ?>
            <hr class="wp-header-end">
			<?php $this->do_page_tabs(); ?>
			<?php

			$method        = sprintf( '%s_%s', $this->get_current_tab(), $this->get_current_action() );
			$backup_method = sprintf( '%s_%s', $this->get_current_tab(), 'view' );

			if ( method_exists( $this, $method ) ) {
				call_user_func( [ $this, $method ] );
			} else if ( has_action( "groundhogg/admin/{$this->get_slug()}/display/{$method}" ) ) {
				do_action( "groundhogg/admin/{$this->get_slug()}/display/{$method}", $this );
			} else if ( method_exists( $this, $backup_method ) ) {
				call_user_func( [ $this, $backup_method ] );
			}

			?>
        </div>
		<?php

	}

	/**
	 * Output the date range picker
	 */
	protected function range_picker() {
		?>
        <div id="groundhogg-datepicker-wrap">
            <div class="daterange daterange--double groundhogg-datepicker" id="groundhogg-datepicker"></div>
        </div>
		<?php
	}

	/**
	 * Overview
	 */
	public function overview_view() {
		include dirname( __FILE__ ) . '/views/overview.php';
	}


	/**
	 * Contacts
	 */
	public function contacts_view() {
		include dirname( __FILE__ ) . '/views/contacts.php';
	}


	/**
	 * Email
	 */
	public function email_view() {
		include dirname( __FILE__ ) . '/views/email.php';
	}


	/**
	 * Contacts
	 */
	public function funnels_view() {
		include dirname( __FILE__ ) . '/views/funnels.php';
	}



	public function refresh_report_data() {

		$start = strtotime( sanitize_text_field( get_post_var( 'start' ) ) );
		$end   = strtotime( sanitize_text_field( get_post_var( 'end' ) ) );

		$reports = map_deep( get_post_var( 'reports' ), 'sanitize_key' );

		$reporting = new Reports( $start, $end );

		$results = [];

		foreach ( $reports as $report_id ) {
			$results[ $report_id ] = $reporting->get_data( $report_id );
		}

		wp_send_json_success( [
			'start'   => $start,
			'end'     => $end,
			'reports' => $results
		] );
	}
}