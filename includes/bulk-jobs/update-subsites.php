<?php

namespace Groundhogg\Bulk_Jobs;

use Groundhogg\Plugin;
use function Groundhogg\html;
use function Groundhogg\white_labeled_name;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Update_subsites extends Bulk_Job {

	public function __construct() {
		add_action( 'admin_init', [ $this, 'show_upgrade_prompt' ] );
		parent::__construct();
	}

	public function show_upgrade_prompt() {


		if (  ! is_main_site() || ! is_plugin_active_for_network( 'groundhogg/groundhogg.php' ) || ! is_multisite() ) {
			return;
		}

		$update_button = html()->e( 'a', [
			'href'  => $this->get_start_url(),
			'class' => 'button button-secondary'
		], __( 'Migrate now!', 'groundhogg' ) );

		$notice = sprintf( __( "%s requires a database migration. Consider backing up your site before migrating. </p><p>%s", 'groundhogg' ), white_labeled_name(), $update_button );

		Plugin::$instance->notices->add( 'db-update', $notice );
	}

	public function get_action() {

		return 'update_subsites';
	}

	public function query( $items ) {

		$ids = [];

		if ( is_multisite() ) {
			foreach ( get_sites() as $site ) {
				$ids [] = $site->blog_id;

			}
		}

		return $ids;

	}

	public function max_items( $max, $items ) {
		return min( 1, intval( ini_get( 'max_input_vars' ) ) );
	}

	protected function pre_loop() {
		// TODO: Implement pre_loop() method.
	}

	protected function process_item( $item ) {

		$blog_id = absint( $item );
		switch_to_blog( $blog_id );
		do_action( 'groundhogg/force_updates' );
		restore_current_blog();

	}

	protected function post_loop() {
		// TODO: Implement post_loop() method.
	}

	protected function clean_up() {
		// TODO: Implement clean_up() method.
	}
}
