<?php

namespace Groundhogg\Admin\Reports;

use Groundhogg\Admin\Reports\Views\Overview;
use Groundhogg\Admin\Tabbed_Admin_Page;
use Groundhogg\Plugin;

class Reports_Page extends Tabbed_Admin_Page
{

	/**
	 * Add Ajax actions...
	 *
	 * @return mixed
	 */
	protected function add_ajax_actions() {
		// TODO: Implement add_ajax_actions() method.
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
		// TODO: Implement scripts() method.
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
			]
		];
	}

	public function page() {

		do_action( "groundhogg/admin/{$this->get_slug()}", $this );
		do_action( "groundhogg/admin/{$this->get_slug()}/{$this->get_current_tab()}", $this );

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php echo $this->get_title(); ?></h1>
			<?php $this->do_title_actions(); ?>
			<?php $this->notices(); ?>
			<hr class="wp-header-end">
			<?php $this->do_page_tabs(); ?>
			<?php

			$method = sprintf( '%s_%s', $this->get_current_tab(), $this->get_current_action() );
			$backup_method = sprintf( '%s_%s', $this->get_current_tab(), 'view' );

			if ( method_exists( $this, $method ) ){
				call_user_func( [ $this, $method ] );
			} else if ( has_action( "groundhogg/admin/{$this->get_slug()}/display/{$method}" ) ){
				do_action( "groundhogg/admin/{$this->get_slug()}/display/{$method}", $this );
			} else if ( method_exists( $this, $backup_method ) ) {
				call_user_func( [ $this, $backup_method ] );
			}

			?>
		</div>
		<?php

	}


	public function overview_view()
    {


	}

}