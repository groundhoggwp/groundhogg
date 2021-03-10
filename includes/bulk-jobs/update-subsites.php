<?php

namespace Groundhogg\Bulk_Jobs;

use function Groundhogg\get_url_var;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Update_Subsites extends Bulk_Job {

	public function get_action() {
		return 'update_subsites';
	}

	public function query( $items ) {

		$updater = sanitize_text_field( get_url_var( 'updater' ) );

		$items = [];

		if ( is_multisite() ) {
			foreach ( get_sites() as $site ) {
				$items[] = [
					'blog_id' => $site->blog_id,
					'updater' => $updater
				];

			}
		}

		return $items;

	}

	public function max_items( $max, $items ) {
		return 1;
	}

	protected function pre_loop() {
		// TODO: Implement pre_loop() method.
	}

	protected function process_item( $item ) {

		$blog_id = absint( $item['blog_id'] );
		$updater = sanitize_text_field( $item['updater'] );

		switch_to_blog( $blog_id );
		do_action( "groundhogg/updater/$updater/force_updates" );
		restore_current_blog();

	}

	protected function post_loop() {
		// TODO: Implement post_loop() method.
	}

	protected function clean_up() {
		// TODO: Implement clean_up() method.
	}
}
