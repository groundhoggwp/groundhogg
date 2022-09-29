<?php

namespace Groundhogg\Admin\Dashboard\Widgets;


use Groundhogg\Funnel;
use function Groundhogg\get_db;
use function Groundhogg\html;
use function Groundhogg\percentage;
use Groundhogg\Plugin;

/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/27/2018
 * Time: 9:13 AM
 */
class Funnel_Breakdown extends Category_Graph {

	/**
	 * @return bool
	 */
	protected function hide_if_no_data() {
		$has_funnels = get_db( 'funnels' )->count( [ 'status' => 'active' ] );

		if ( $has_funnels ) {
			return false;
		}

		return true;
	}

	public function get_id() {
		return 'funnel_breakdown';
	}

	public function get_name() {
		return __( 'Funnel Breakdown', 'groundhogg' );
	}

	/**
	 * @return int
	 */
	protected function get_funnel_id() {
		return Plugin::$instance->reporting->get_report( 'complete_funnel_activity' )->get_funnel_id();
	}

	protected function form() {
		$funnels = get_db( 'funnels' );
		$funnels = $funnels->query( [ 'status' => 'active' ] );

		$options = [];

		foreach ( $funnels as $funnel ) {
			$funnel                       = new Funnel( absint( $funnel->ID ) );
			$options[ $funnel->get_id() ] = $funnel->get_title();
		}

		?>
        <div class="actions">
            <form method="get" action="">
				<?php

				echo html()->hidden_GET_inputs();

				$args = array(
					'name'        => 'funnel',
					'id'          => 'funnel',
					'options'     => $options,
					'selected'    => [ $this->get_funnel_id() ],
					'option_none' => false,
				);
				echo Plugin::$instance->utils->html->dropdown( $args );
				submit_button( __( 'Update' ), 'secondary', 'update_funnel_breakdown', false );
				?>
            </form>
        </div>
		<?php
	}

	/**
	 * Return several reports used rather than just 1.
	 *
	 * @return string[]
	 */
	protected function get_report_ids() {
		return [
			'complete_funnel_activity',
			'waiting_funnel_activity'
		];
	}

	/**
	 * @param $data
	 *
	 * @return mixed
	 */
	public function normalize_complete_funnel_activity( $data ) {
		return $data;
	}

	/**
	 * @param $data
	 *
	 * @return mixed
	 */
	public function normalize_waiting_funnel_activity( $data ) {
		return $data;
	}

	/**
	 * Any additional information needed for the widget.
	 *
	 * @return void
	 */
	protected function extra_widget_info() {
		$this->form();

		$html = Plugin::$instance->utils->html;

		if ( empty( $this->dataset ) ) {
			return;
		}

		$complete = $this->dataset[0]['data'];
		$waiting  = $this->dataset[1]['data'];

		$rows = [];

		if ( empty( $complete ) ) {
			return;
		}

		$total_complete = max( wp_list_pluck( $complete, 1 ) );

		foreach ( $complete as $i => $set ) {
			$rows[] = [
				$html->wrap( $set[0], 'a', [ 'href' => admin_url( sprintf( 'admin.php?page=gh_funnels&action=edit&funnel=%d', $this->get_funnel_id() ) ) ] ),
				$html->wrap( $set[1] . ' (' . percentage( $total_complete, $set[1] ) . '%)', 'span', [ 'class' => 'number-total' ] ),
				$html->wrap( $waiting[ $i ][1], 'span', [ 'class' => 'number-total' ] ),
			];
		}

		$html->list_table(
			[ 'class' => 'funnel_breakdown' ],
			[
				__( 'Step', 'groundhogg' ),
				__( 'Complete', 'groundhogg' ),
				__( 'Waiting', 'groundhogg' ),
			],
			$rows,
			false
		);
	}
}
