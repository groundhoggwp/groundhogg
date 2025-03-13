<?php

namespace Groundhogg\Cli;

use Groundhogg\Step;
use function Groundhogg\get_db;
use function WP_CLI\Utils\make_progress_bar;

/**
 * For testing only
 *
 * ## EXAMPLES
 *
 */
class Tests {

	/**
	 * Test step conditionals
	 *
	 * ## OPTIONS
	 *
	 * <method>
	 * : Method to call, like is_after
	 *
	 * <stepA>
	 * : Step to compare
	 *
	 * <stepB>
	 * : Step to compare against
	 *
	 * ## EXAMPLES
	 *
	 *     wp groundhogg-tests step is_after 123 456
	 *
	 * @when after_wp_load
	 */
	function step( $args ){
		$method = $args[0];
		$stepA = $args[1];
		$stepB = $args[2];

		$stepA = new Step( $stepA );
		$stepB = new Step( $stepB );

		if ( ! $stepA->exists() || ! $stepB->exists() ) {
			\WP_CLI::error( 'The given steps dont exist.' );
		}

		if ( ! method_exists( $stepA, $method ) ) {
			\WP_CLI::error( 'The requested method does not exist' );
		}

		if ( call_user_func( array( $stepA, $method ), $stepB ) ) {
			\WP_CLI::success( 'True' );
		} else {
			\WP_CLI::error( 'False' );
		}
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
