<?php

namespace Groundhogg\cli;

use Groundhogg\Contact_Query;
use Groundhogg\Email;
use Groundhogg\Funnel;
use Groundhogg\Utils\DateTimeHelper;
use function Groundhogg\get_db;
use function WP_CLI\Utils\make_progress_bar;

/**
 * Manipulate tables
 *
 * ## EXAMPLES
 *
 *     # Drop a table
 *     $ wp groundhogg/table drop events
 *     Success: Dropped wp_gh_events
 *
 *     # Create a table
 *     $ wp groundhogg/table create events
 *     Success: Created wp_gh_events
 *
 *     # Reset a table; Drops, then recreates it
 *     $ wp groundhogg/table reset events
 *     Success: Reset wp_gh_events
 *
 *     # Truncate a table
 *     $ wp groundhogg/table truncate events
 *     Success: Truncate wp_gh_events
 */
class Table {

	/**
	 * Drop a table
	 *
	 * ## OPTIONS
	 *
	 * <table>
	 * : internal table name
	 *
	 * ## EXAMPLES
	 *
	 *     wp groundhogg/table drop events
	 *
	 * @when after_wp_load
	 */
	function drop( $args ){
		$table = get_db( $args[0] );

		if ( ! $table ){
			\WP_CLI::error( sprintf( 'Table %s is not registered.', $args[0] ) );
		}

		$table->drop();

		\WP_CLI::success( sprintf( 'Dropped %s', $table->table_name ) );
	}

	/**
	 * Create a table
	 *
	 * ## OPTIONS
	 *
	 * <table>
	 * : internal table name
	 *
	 * ## EXAMPLES
	 *
	 *     wp groundhogg/table create events
	 *
	 * @when after_wp_load
	 */
	function create( $args ){
		$table = get_db( $args[0] );

		if ( ! $table ){
			\WP_CLI::error( sprintf( 'Table %s is not registered.', $args[0] ) );
		}

		$table->create_table();

		\WP_CLI::success( sprintf( 'Created %s', $table->table_name ) );
	}

	/**
	 * Reset a table. Drops, then re-creates
	 *
	 * ## OPTIONS
	 *
	 * <table>
	 * : internal table name
	 *
	 * ## EXAMPLES
	 *
	 *     wp groundhogg/table reset events
	 *
	 * @when after_wp_load
	 */
	function reset( $args ){
		$table = get_db( $args[0] );

		if ( ! $table ){
			\WP_CLI::error( sprintf( 'Table %s is not registered.', $args[0] ) );
		}

		$table->drop();
		$table->create_table();

		\WP_CLI::success( sprintf( 'Reset %s', $table->table_name ) );
	}

	/**
	 * Truncates a table
	 *
	 * ## OPTIONS
	 *
	 * <table>
	 * : internal table name
	 *
	 * ## EXAMPLES
	 *
	 *     wp groundhogg/table truncate events
	 *
	 * @when after_wp_load
	 */
	function truncate( $args ){

		$table = get_db( $args[0] );

		if ( ! $table ){
			\WP_CLI::error( sprintf( 'Table %s is not registered.', $args[0] ) );
		}

		$table->truncate();

		\WP_CLI::success( sprintf( 'Truncated %s', $table->table_name ) );
	}

}
