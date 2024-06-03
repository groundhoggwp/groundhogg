<?php

namespace Groundhogg\Cli;

use function Groundhogg\get_db;
use function WP_CLI\Utils\make_progress_bar;

/**
 * Manipulate tables
 *
 * ## EXAMPLES
 *
 *     # Drop a table
 *     $ wp groundhogg-table drop events
 *     Success: Dropped wp_gh_events
 *
 *     # Create a table
 *     $ wp groundhogg-table create events
 *     Success: Created wp_gh_events
 *
 *     # Reset a table; Drops, then recreates it
 *     $ wp groundhogg-table reset events
 *     Success: Reset wp_gh_events
 *
 *     # Truncate a table
 *     $ wp groundhogg-table truncate events
 *     Success: Truncated wp_gh_events
 */
class Table {

	/**
	 * Drop a table
	 *
	 * ## OPTIONS
	 *
	 * <table>...
	 * : internal table name(s)
	 *
	 * ## EXAMPLES
	 *
	 *     wp groundhogg-table drop events
	 *
	 * @when after_wp_load
	 */
	function drop( $args ){

		$dropped = 0;

		foreach ( $args as $table_id ){
			$table = get_db( $table_id );

			if ( ! $table ){
				\WP_CLI::error( sprintf( 'Table %s is not registered.', $args[0] ) );
			}

			$table->drop();

			\WP_CLI::log( sprintf( 'Dropped `%s`', $table->table_name ) );

			$dropped++;
		}

		\WP_CLI::success( sprintf( 'Dropped %d tables', $dropped ) );
	}

	/**
	 * Create a table
	 *
	 * ## OPTIONS
	 *
	 * <table>...
	 * : internal table name(s)
	 *
	 * ## EXAMPLES
	 *
	 *     wp groundhogg-table create events
	 *
	 * @when after_wp_load
	 */
	function create( $args ){
		$created = 0;

		foreach ( $args as $table_id ){
			$table = get_db( $table_id );

			if ( ! $table ){
				\WP_CLI::error( sprintf( 'Table %s is not registered.', $args[0] ) );
			}

			$table->create_table();

			\WP_CLI::log( sprintf( 'Created `%s`', $table->table_name ) );

			$created++;
		}

		\WP_CLI::success( sprintf( 'Created %d tables', $created ) );
	}

	/**
	 * Reset a table. Drops, then re-creates
	 *
	 * ## OPTIONS
	 *
	 * <table>...
	 * : internal table name(s)
	 *
	 * ## EXAMPLES
	 *
	 *     wp groundhogg-table reset events
	 *
	 * @when after_wp_load
	 */
	function reset( $args ){
		$reset = 0;

		foreach ( $args as $table_id ){
			$table = get_db( $table_id );

			if ( ! $table ){
				\WP_CLI::error( sprintf( 'Table %s is not registered.', $args[0] ) );
			}

			$table->drop();
			$table->create_table();

			\WP_CLI::log( sprintf( 'Reset `%s`', $table->table_name ) );

			$reset++;
		}

		\WP_CLI::success( sprintf( 'Reset %d tables', $reset ) );
	}

	/**
	 * Truncates a table
	 *
	 * ## OPTIONS
	 *
	 * <table>...
	 * : internal table name(s)
	 *
	 * ## EXAMPLES
	 *
	 *     wp groundhogg-table truncate events
	 *
	 * @when after_wp_load
	 */
	function truncate( $args ){

		$truncated = 0;

		foreach ( $args as $table_id ){
			$table = get_db( $table_id );

			if ( ! $table ){
				\WP_CLI::error( sprintf( 'Table %s is not registered.', $args[0] ) );
			}

			$table->truncate();

			\WP_CLI::log( sprintf( 'Truncated `%s`', $table->table_name ) );

			$truncated++;
		}

		\WP_CLI::success( sprintf( 'Truncated %d tables', $truncated ) );
	}

}
