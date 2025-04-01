<?php

namespace Groundhogg\Cli;

use Groundhogg\Step;
use function Groundhogg\get_contactdata;
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
	function stepcmp( $args ){
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
	 * Test step branch conditionals
	 *
	 * ## OPTIONS
	 *
	 * <step>
	 * : the step to test
	 *
	 * <branch>
	 * : The branch to test
	 *
	 * <contact>
	 * : The contact to test
	 *
	 * ## EXAMPLES
	 *
	 *     wp groundhogg-tests branchtest 123 foo 456
	 *
	 * @when after_wp_load
	 */
	function branchtest( $args ) {

		$stepId = $args[0];
		$branch = $args[1];
		$contact = $args[2];

		$step = new Step( $stepId );
		$contact = get_contactdata( $contact );

		if ( ! $step->is_branch_logic() ) {
			\WP_CLI::error( 'Not a branch logic step' );
		}

		if ( $step->get_step_element()->matches_branch_conditions( $branch, $contact ) ) {
			\WP_CLI::success( 'True' );
		} else {
			\WP_CLI::error( 'False' );
		}
	}

	/**
	 * Test step branch conditionals
	 *
	 * ## OPTIONS
	 *
	 * <step>
	 * : the step to test
	 *
	 * <contact>
	 * : The contact to test
	 *
	 * ## EXAMPLES
	 *
	 *     wp groundhogg-tests logicbranch 123 456
	 *
	 * @when after_wp_load
	 */
	function logicbranch( $args ) {

		$stepId = $args[0];
		$contact = $args[1];

		$step = new Step( $stepId );
		$contact = get_contactdata( $contact );

		if ( ! $step->is_branch_logic() ) {
			\WP_CLI::error( 'Not a branch logic step' );
		}

		\WP_CLI::success( $step->get_step_element()->get_logic_branch( $contact ) );
	}

}
