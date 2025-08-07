<?php

namespace Groundhogg\steps\logic;

use Groundhogg\Contact;
use Groundhogg\Step;
use function Groundhogg\array_filter_splice;
use function Groundhogg\db;
use function Groundhogg\get_post_var;
use function Groundhogg\maybe_explode;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-04-29
 * Time: 9:45 AM
 */
abstract class Branch_Logic extends Logic {

	abstract public function get_branches();

	public function get_sub_group() {
		return 'branching';
	}

	abstract protected function get_branch_name( $branch );

    public function _get_branch_name( $branch ) {
	    return $this->get_branch_name( $branch );
    }

	/**
	 * Whether the contact matches the conditions for the current branch
	 *
	 * @param string  $branch  the branch key will be in the form of '<stepId>-<branch>' so parsing may be required depending on the step's settings
	 * @param Contact $contact the contact to check for the conditions
	 *
	 * @return bool
	 */
	abstract public function matches_branch_conditions( string $branch, Contact $contact );

	/**
	 * Get the first step of the first branch that matches the required conditions to move forward in the funnel
	 *
	 * @param Contact $contact the contact to get the next branch for...
	 *
	 * @return Step|false
	 */
	public function get_logic_action( Contact $contact ) {

        $branch = $this->get_logic_branch( $contact );

        if ( ! $branch ){
            return false;
        }

        return $this->get_first_of_branch( $this->maybe_prefix_branch( $branch ) );
	}

	/**
     * Get the branch to send contacts down
     *
	 * @param Contact $contact
	 *
	 * @return false|Step
	 */
    public function get_logic_branch( Contact $contact ) {
	    $branches = $this->get_branches();

	    foreach ( $branches as $branch ) {
		    if ( $this->matches_branch_conditions( $branch, $contact ) ) {
			    return $branch;
		    }
	    }

	    return false;
    }

	/**
     * Maybe prefix the step ID to a branch
     *
	 * @param string $branch
	 *
	 * @return string
	 */
	public function maybe_prefix_branch( string $branch ) {
		$current_step = $this->get_current_step();
		$step_prefix  = $current_step->ID . '-';

		// make sure the branch is started with the step prefix
		if ( ! str_starts_with( $branch, $step_prefix ) ) {
			$branch = $step_prefix . $branch;
		}

		return $branch;
	}

	/**
	 * Return the first step of a given branch
	 *
	 * @param string $branch in format of '<stepId>-<branch>'
	 *
	 * @return Step|false
	 */
	public function get_first_of_branch( string $branch ) {

		$branch = $this->maybe_prefix_branch( $branch );
		$steps  = $this->get_sub_steps( $branch );

		return ! empty( $steps ) ? $steps[0] : false; // any proceeding action
	}

	public function sortable_item( $step ) {

        $this->set_current_step( $step );
		$branch_steps = $this->get_sub_steps();

		foreach ( $branch_steps as $branch_step ) {
			$branch_step->get_step_element()->validate_settings( $branch_step );
		}

		// reset the current step to the current one
		$this->set_current_step( $step );

		?>
        <div class="sortable-item logic branch-logic" data-type="<?php echo esc_attr( $step->get_type() ); ?>" data-group="<?php echo esc_attr( $step->get_group() ); ?>">
			<?php $this->add_step_button( 'before-' . $step->ID ); ?>
            <div class="flow-line"></div>
			<?php $this->__sortable_item( $step ); ?>
            <div class="display-flex align-top step-branches">
				<?php foreach ( $this->get_branches() as $branch_id ):

					$steps = array_filter_splice( $branch_steps, function ( Step $step ) use ( $branch_id ) {
						return $step->branch_is( $branch_id );
					} );

					$classes = $this->get_branch_classes( $branch_id );

					?>
                    <div class="split-branch <?php echo $classes ?>">
                        <div class="logic-line line-above">
                            <span id="<?php echo esc_attr( 'branch-name-indicator-' . $branch_id ) ?>" class="path-indicator"><?php esc_html_e( $this->get_branch_name( $branch_id ) ); ?></span>
                        </div>
                        <div id="<?php echo esc_attr( 'branch-' . $branch_id ); ?>" class="step-branch" data-branch="<?php echo esc_attr( $branch_id ); ?>">
                            <?php foreach ( $steps as $branch_step ) {
								$branch_step->sortable_item();
							}

                            // reset the current step to the current one
	                        $this->set_current_step( $step );

							$add_button_id = 'in-branch-' . $branch_id;

							$this->add_step_button( [
								'id'      => $add_button_id,
								'tooltip' => sprintf( 'Add step in %s', $this->get_branch_name( $branch_id ) ),
								'class'   => 'add-action',
							] );

							?></div>
                        <div class="logic-line line-below"></div>
                        <div class="logic-line line-below-after"></div>
                    </div>
				<?php endforeach; ?>
            </div>
        </div>
		<?php
	}

	/**
	 * Get steps within a specific branch
	 *
	 * @param string|array $branch
	 *
	 * @return Step[]
	 */
	public function get_sub_steps( $branch = false ) {

		$steps    = $this->get_current_step()->get_funnel()->get_steps();
		$branches = ! empty( $branch ) ? maybe_explode( $branch ) : $this->get_branches();

		$branches = array_map( [ $this, 'maybe_prefix_branch' ], $branches );

		// use array_values to reindex the array
		return array_values( array_filter( $steps, function ( Step $step ) use ( $branches ) {
			return in_array( $step->branch, $branches );
		} ) );
	}

	/**
	 * Recursively delete all the steps in the branch
	 *
	 * @param Step $step
	 *
	 * @return void
	 */
	public function delete( Step $step ) {

		$branch_steps = $this->get_sub_steps();

		foreach ( $branch_steps as $branch_step ) {
			$branch_step->delete(); // this might change the current step
		}

		// reset to the current step
		$this->set_current_step( $step );
	}

	/**
	 * Duplicate the steps in the branches
	 *
	 * @param Step $new
	 * @param Step $original
	 *
	 * @return void
	 */
	public function duplicate( $new, $original ) {

		// don't duplicate sub steps
		if ( get_post_var( '__ignore_inner' ) ) {
			return;
		}

		// get the OG sub steps
		$og_sub_steps = $original->get_step_element()->get_sub_steps();

		foreach ( $og_sub_steps as $sub_step ) {
			// duplicate the previous step
			$new_sub_step = $sub_step->duplicate( [
				'step_status' => 'inactive', // must be inactive to start,
				'branch'      => str_replace( "$original->ID", "$new->ID", $sub_step->branch ),
				'funnel_id'   => $new->funnel_id
			] );
		}

		$this->set_current_step( $original );
	}

	protected function get_branch_classes( $branch_id ): string {
		return '';
	}

}
