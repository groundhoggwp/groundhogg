<?php
/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/27/2018
 * Time: 9:13 AM
 */
class WPGH_Funnel_Breakdown_Widget extends WPGH_Line_Graph_Report_V2
{

    /**
     * WPGH_Report_V2 constructor.
     */
    public function __construct()
    {
        $this->wid = 'funnel_breakdown_widget';
        $this->name = _x( 'Funnel Breakdown', 'widget_name', 'groundhogg' );

        parent::__construct();
    }

	/**
	 * @return string
	 */
    public function get_mode()
    {
        return "categories";
    }

    public function get_data() {

	    $break_down_funnel_id = intval( $this->get_url_var( 'breakdown_funnel_id', $this->get_option( 'breakdown_funnel_id' ) ) );

	    if ( ! $break_down_funnel_id ){
		    $funnels = WPGH()->funnels->get_funnels( array( 'status' => 'active' ) );
		    $break_down_funnel_id = $funnels[0]->ID;
	    }

	    $this->update_options(
		    array( 'breakdown_funnel_id' => $break_down_funnel_id )
	    );


	    $steps = WPGH()->steps->get_steps( array(
		    'funnel_id'  => $break_down_funnel_id,
//		    'step_group'  => 'benchmark',
	    ) );

	    if ( empty( $steps ) ){
		    return [];
	    }

	    $ds = array();
	    $dataset1 = array();
	    $dataset2 = array();

	    foreach ( $steps as $i => $step ) {

		    $query = new WPGH_Contact_Query();

		    $args = array(
			    'report' => array(
				    'funnel' => $break_down_funnel_id,
				    'step' => $step->ID,
				    'status' => 'complete',
				    'start' => $this->start_time,
				    'end' => $this->end_time,
			    )
		    );

		    $count = count($query->query($args));

		    $dataset1[] = array( ( $i + 1 ) .'. '. $step->step_title , $count );

		    $args = array(
			    'report' => array(
				    'funnel' => intval(  $_REQUEST[ 'funnel' ] ),
				    'step' => $step->ID,
				    'status' => 'waiting'
			    )
		    );

		    $count = count($query->query($args));

		    $dataset2[] = array( ( $i + 1 ) .'. '. $step->step_title , $count );

	    }

	    $ds[] = array(
		    'label' => _x( 'Completed Events', 'stats', 'groundhogg' ),
		    'data'  => $dataset1
	    ) ;
	    $ds[] = array(
		    'label' => __( 'Waiting Contacts', 'stats', 'groundhogg' ),
		    'data'  => $dataset2
	    ) ;

	    return $ds;

    }

	public function extra_widget_info()
    {

        /*Get all the funnels */
        $funnels = WPGH()->funnels->get_funnels( array( 'status' => 'active' ) );

        if ( empty( $funnels ) ){
            printf( '<p>%s</p>', _x( 'You have no active funnels.', 'notice', 'groundhogg' ) );
            return;
        }

        $options = array();

        foreach ( $funnels as $funnel ){
            $options[ $funnel->ID ] = $funnel->title;
        }

	    $break_down_funnel_id = intval( $this->get_url_var( 'breakdown_funnel_id', $this->get_option( 'breakdown_funnel_id' ) ) );

	    ?>
        <div class="actions">
            <form method="get" action="">
                <?php

                $this->form_reporting_inputs();

                $args = array(
                    'name'      => 'breakdown_funnel_id',
                    'id'        => 'breakdown_funnel_id',
                    'options'   => $options,
                    'selected' => $break_down_funnel_id,
                ); echo WPGH()->html->dropdown( $args );

                submit_button( __( 'Update' ), 'secondary', 'update_funnel_breakdown', false );

                ?>
            </form>
        </div>
        <?php

        $benchmarks = WPGH()->steps->get_steps( array(
            'step_group' => 'benchmark',
            'funnel_id'  => $break_down_funnel_id
        ) );

        if ( empty( $benchmarks ) ){
            return;
        }

        ?>
        <hr>
        <table class="chart-summary">
        <thead>
        <tr>
            <th><?php _ex( 'Benchmark', 'column_title','groundhogg' ); ?></th>
            <th><?php _ex( 'Contacts', 'column_title', 'groundhogg' ); ?></th>
            <th><?php _ex( '/ Total', 'column_title', 'groundhogg' ); ?></th>
            <th><?php _ex( '/ Previous Step', 'column_title', 'groundhogg' ); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php

        $total_count = 0;
        $prev_count = 0;

        foreach ( $benchmarks as $i => $benchmark ):

            $count = WPGH()->events->count( array(
                'step_id' => $benchmark->ID,
                'start'   => $this->start_time,
                'end'     => $this->end_time,
                'status'  => 'complete'
            ) );

            if ( $i === 0 ){
                $total_count = $count;
            }

            ?>
            <tr>
                <td class=""><?php printf( '<a href="%s">%s</a>', admin_url( 'admin.php?page=gh_funnels&action=edit&funnel=' . $benchmark->funnel_id . '&step=' . $benchmark->ID ), $benchmark->step_title ); ?></td>
                <?php if ( $i === 0 ): ?>
                <td class="summary-total"><?php printf( '%d', $count ); ?></td>
                <?php else: ?>
                    <td class="summary-total"><?php printf( '%d', $count ); ?></td>
                    <td class="summary-total"><?php printf( '%d%%', ceil( ( $count / max( $total_count, 1 ) ) * 100 ) ); ?></td>
                    <td class="summary-total"><?php printf( '%d%%', ceil( ( $count / max( $prev_count, 1 ) ) * 100 ) ); ?></td>
                <?php endif; ?>
            </tr>
            <?php

            $prev_count = $count;

        endforeach;

        ?></tbody></table><?php

        $this->export_button();

    }

    /**
     * Return export info in friendly format
     *
     * @return array|string
     */
    protected function get_export_data()
    {
        $break_down_funnel_id = $this->get_option( 'breakdown_funnel_id' );

        if ( ! $break_down_funnel_id ){
            return _x( 'Please select a funnel first.', 'notice', 'groundhogg' );
        }

        $benchmarks = WPGH()->steps->get_steps( array(
            'step_group' => 'benchmark',
            'funnel_id'  => $break_down_funnel_id
        ) );

        if ( empty( $benchmarks ) ){
            return _x( 'This funnel has no benchmarks.', 'notice', 'groundhogg' );
        }

        $export_info = array();

        $total_count = 0;
        $prev_count = 0;

        foreach ( $benchmarks as $i => $benchmark ):

            $count = WPGH()->events->count( array(
                'step_id' => $benchmark->ID,
                'start'   => $this->start_time,
                'end'     => $this->end_time,
                'status'  => 'complete'
            ) );

            if ( $i === 0 ){
                $total_count = $count;
                $by_total = '';
                $by_prev = '';
            } else {
                $by_total = ceil( ( $count / max( $total_count, 1 ) ) * 100 )  . '%';
                $by_prev = ceil( ( $count / max( $prev_count, 1 ) ) * 100 )  . '%';
            }

            $export_info[] = array(
                _x( 'Benchmark', 'column_title', 'groundhogg' )     => $benchmark->step_title,
                _x( 'Contacts', 'column_title', 'groundhogg' )      => $count,
                _x( '/ Total', 'column_title', 'groundhogg' )       => $by_total,
                _x( '/ Previous', 'column_title', 'groundhogg' )    => $by_prev,
            );

            $prev_count = $count;

        endforeach;

        return $export_info;
    }
}