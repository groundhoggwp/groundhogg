<?php

namespace Groundhogg\Steps\Logic;

use Groundhogg\Contact;
use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Step;
use Groundhogg\Steps\Funnel_Step;
use function Groundhogg\array_filter_splice;
use function Groundhogg\html;
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
abstract class Logic extends Funnel_Step {

	const GROUP = 'logic';

	/**
	 *
	 * @return string
	 */
	final public function get_group() {
		return self::LOGIC;
	}

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
	public function get_branch_action( Contact $contact ) {

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

		$classes = [
			$step->get_group(),
			$step->get_type(),
			$step->step_status,
		];

		if ( $step->has_errors() || $this->has_errors() ) {
			$classes[] = 'has-errors';
		}

		if ( $step->has_changes() ) {
			$classes[] = 'has-changes';
		}

		$classes      = apply_filters( 'groundhogg/steps/sortable/classes', $classes, $step, $this );
		$branch_steps = $this->get_sub_steps();

		foreach ( $branch_steps as $branch_step ) {
			$branch_step->get_step_element()->validate_settings( $branch_step );
		}

		// reset the current step to the current one
		$this->set_current_step( $step );

		?>
		<div id="step-<?php echo $step->get_id(); ?>"
		     data-id="<?php echo $step->get_id(); ?>"
		     data-type="<?php esc_attr_e( $this->get_type() ); ?>"
		     class="sortable-item logic">

			<input type="hidden" name="step_ids[]" value="<?php echo $step->get_id(); ?>">
			<input type="hidden" id="<?php echo $this->setting_id_prefix( 'branch' ) ?>" name="<?php echo $this->setting_name_prefix( 'branch' ) ?>" value="<?php esc_attr_e( $step->branch ); ?>">

			<div class="step <?php echo implode( ' ', $classes ) ?>"
			     data-id="<?php echo $step->get_id(); ?>"
			     data-type="<?php esc_attr_e( $this->get_type() ); ?>">

				<div class="actions has-box-shadow">
                    <!-- DUPLICATE -->
                    <button title="Duplicate" type="button" class="gh-button secondary text icon duplicate-step">
                        <span class="dashicons dashicons-admin-page"></span>
                        <div class="gh-tooltip top">Duplicate</div>
                    </button>
                    <!-- DELETE -->
                    <button title="Delete" type="button" class="gh-button danger text icon delete-step">
                        <span class="dashicons dashicons-trash"></span>
                        <div class="gh-tooltip top">Delete</div>
                    </button>
				</div>

				<div class="hndle">
					<?php

					echo html()->e( 'div', [
						'class' => 'hndle-icon'
					], $this->get_icon_svg() );

					?>
					<div>
						<?php
						echo html()->e( 'span', [
							'class' => 'step-title',
						], $this->get_title( $step ) );

						echo html()->e( 'span', [
							'class' => 'step-name',
						], $this->get_name() );
						?>
					</div>
				</div>
			</div>
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
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Also include all the settings of nested steps within the branches
	 *
	 * @param $step
	 *
	 * @return void
	 */
	public function html_v2( $step ) {

		parent::html_v2( $step );

		$branch_steps = $this->get_sub_steps();

		foreach ( $branch_steps as $branch_step ) {
			$branch_step->html_v2( $branch_step );
		}
	}

	public function get_sub_steps( $branch = false ) {
		$branches = ! empty( $branch ) ? maybe_explode( $branch ) : $this->get_branches();

		return array_filter( $this->get_current_step()->get_funnel()->get_steps_for_editor(), function ( Step $step ) use ( $branches ) {
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

}
