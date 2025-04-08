<?php

namespace Groundhogg\Cli;

use Groundhogg\Admin\Funnels\Simulator;
use Groundhogg\Step;
use function cli\prompt;
use function Groundhogg\get_array_var;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_post_var;
use function WP_CLI\Utils\format_items;
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
	 * <step>
	 * : id of the step to test
	 *
	 * <contact>
	 * : id of the contact to test
	 *
	 * ## EXAMPLES
	 *
	 *     wp groundhogg-tests testnext 123 456
	 *
	 * @when after_wp_load
	 */
	function testnext( $args ) {
		$stepId  = $args[0];
		$step    = new Step( $stepId );
		$contact = get_contactdata( $args[1] );

		if ( ! $step->exists() ) {
			\WP_CLI::error( 'The given steps dont exist.' );
		}

		while ( $step ) {
			\WP_CLI::log( $step->get_title() );
			$step = $step->get_next_action( $contact );
		}

		\WP_CLI::success( 'Fin.' );

	}

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
	function stepcmp( $args ) {
		$method = $args[0];
		$stepA  = $args[1];
		$stepB  = $args[2];

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

		$stepId  = $args[0];
		$branch  = $args[1];
		$contact = $args[2];

		$step    = new Step( $stepId );
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

		$stepId  = $args[0];
		$contact = $args[1];

		$step    = new Step( $stepId );
		$contact = get_contactdata( $contact );

		if ( ! $step->is_branch_logic() ) {
			\WP_CLI::error( 'Not a branch logic step' );
		}

		\WP_CLI::success( $step->get_step_element()->get_logic_branch( $contact ) );
	}

	/**
	 * Simulate a contact going through a funnel
	 *
	 * ## OPTIONS
	 *
	 * <step>
	 * : the step to test
	 *
	 * <contact>
	 * : The contact to test
	 *
	 * [--dry=<true|false>]
	 * : If true, no actions will be run
	 *
	 * ## EXAMPLES
	 *
	 *     wp groundhogg-tests simulate 123 456 --dry=false
	 *
	 * @when after_wp_load
	 */
	function simulate( $args, $assoc_args ) {

		$stepId  = $args[0];
		$contact = $args[1];

		$dry = filter_var( get_array_var( $assoc_args, 'dry', true ), FILTER_VALIDATE_BOOLEAN );

		$current = new Step( $stepId );
		$contact = get_contactdata( $contact );

		Simulator::simulate( $current, $contact, $dry );
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
	function logicaction( $args ) {

		$stepId  = $args[0];
		$contact = $args[1];

		$step    = new Step( $stepId );
		$contact = get_contactdata( $contact );

		if ( ! $step->is_branch_logic() ) {
			\WP_CLI::error( 'Not a branch logic step' );
		}

		$element = $step->get_step_element();
		$action  = $element->get_logic_action( $contact );

		if ( $action === false ) {
			\WP_CLI::success( 'No action' );
		} else {
			\WP_CLI::success( $action->get_title() );
		}

	}

}
