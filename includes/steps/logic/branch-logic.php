<?php

namespace Groundhogg\steps\logic;

use Groundhogg\Contact;
use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Step;
use function Groundhogg\array_filter_splice;
use function Groundhogg\db;
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

		$branches = $this->get_branches();

		foreach ( $branches as $branch ) {
			if ( $this->matches_branch_conditions( $branch, $contact ) ) {
				return $this->get_first_of_branch( $branch );
			}
		}

		return false;
	}

	public function maybe_prefix_branch( $branch ) {
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
	public function get_first_of_branch( $branch ) {

		$current_step = $this->get_current_step();
		$branch       = $this->maybe_prefix_branch( $branch );

		$query = new Table_Query( 'steps' );
		$query->setOrderby( [ 'step_order', 'ASC' ] )
		      ->setLimit( 1 )
		      ->where()
		      ->equals( 'funnel_id', $current_step->get_funnel_id() )
		      ->equals( 'branch', $branch );

		$next = $query->get_objects( Step::class );

		return ! empty( $next ) ? $next[0] : false; // any proceeding action
	}

	public function sortable_item( $step ) {


		$branch_steps = $this->get_sub_steps();

		foreach ( $branch_steps as $branch_step ) {
			$branch_step->get_step_element()->validate_settings( $branch_step );
		}

		// reset the current step to the current one
		$this->set_current_step( $step );

		?>
        <div class="sortable-item logic" data-type="<?php esc_attr_e( $step->get_type()); ?>" data-group="<?php esc_attr_e( $step->get_group()); ?>">
			<?php parent::sortable_item( $step ); ?>
            <div class="display-flex align-center step-branches">
				<?php foreach ( $this->get_branches() as $branch_id ):

					$steps = array_filter_splice( $branch_steps, function ( $step ) use ( $branch_id ) {
						return $step->branch === $branch_id;
					} );

					?>
                    <div class="split-branch">
                        <div class="logic-line line-above">
                            <span class="path-indicator"><?php esc_html_e( $this->get_branch_name( $branch_id ) ); ?></span>
                        </div>
                        <div class="step-branch" data-branch="<?php esc_attr_e( $branch_id ); ?>"><?php foreach ( $steps as $branch_step ) {
								$branch_step->sortable_item();
							} ?></div>
                        <div class="logic-line line-below"></div>
                        <div class="logic-line line-below-after"></div>
                    </div>
				<?php endforeach; ?>
            </div>
        </div>
		<?php
	}

	public function get_sub_steps( $branch = false ) {

		$steps    = $this->get_current_step()->get_funnel()->get_steps();
		$branches = ! empty( $branch ) ? maybe_explode( $branch ) : $this->get_branches();

		return array_filter( $steps, function ( Step $step ) use ( $branches ) {
			return in_array( $step->branch, $branches );
		} );
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

			// we can delete active steps, so we move the step to the parent branch
			if ( $branch_step->is_active() ) {

				// if the step being deleted is active, move it back to the parent branch
				$branch_step->add_changes( [
					'branch' => $step->branch
				] );

				continue;
			}

			$branch_step->delete(); // this might change the current step
		}

		// reset to the current step
		$this->set_current_step( $step );
	}

	/**
	 * @param Step $step
	 *
	 * @return void
	 */
    public function post_import( $step ) {

        $oldId = $step->get_meta( 'imported_step_id' );

        // we have to update all the branches

        $branches = $this->get_branches();

        foreach ( $branches as $branch ) {

            $parts = explode( '-', $branch );
            $oldbranch = $oldId . '-' . $parts[1];

            db()->steps->update( [
                'branch' => $oldbranch, // old branch
            ], [
	            'branch' => $branch, // new branch
            ] );

        }

    }

}
