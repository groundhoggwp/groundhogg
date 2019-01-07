<?php
/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/27/2018
 * Time: 9:13 AM
 */
class WPGH_Most_Active_Funnels_Widget extends WPGH_Reporting_Widget
{

    /**
     * WPGH_Report_V2 constructor.
     */
    public function __construct()
    {
        $this->wid = 'most_active_funnels_widget';
        $this->name = __( 'Most Active Funnels', 'groundhogg' );

        parent::__construct();
    }

    public function widget()
    {

        /*Get all the funnels */
        $funnels = WPGH()->funnels->get_funnels( array( 'status' => 'active' ) );

        if ( empty( $funnels ) ){
            printf( '<p>%s</p>', __( 'You have no active funnels.', 'groundhogg' ) );
            return;
        }

        /* Num events to $funnel */
        $ordered = array();

        /* Get all events within time range */
        foreach ( $funnels as $funnel ){

            $num_events = WPGH()->events->count( array(
                'funnel_id' => $funnel->ID,
                'start'     => $this->start_time,
                'end'       => $this->end_time,
            ) );

            $ordered[ $num_events ] = $funnel;

        }

        ksort( $ordered );
        $ordered = array_reverse( $ordered, true );

//        if ( count( $ordered ) > 5 ){
//            $ordered = array_slice( $ordered, 0, 5 );
//        }

        ?><table class="chart-summary">
        <thead>
        <tr>
            <th><?php _e( 'Funnel', 'groundhogg' ); ?></th>
            <th><?php _e( 'Active Contacts', 'groundhogg' ); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php

        foreach ( $ordered as $count => $funnel ):

            ?>
            <tr>
                <td><?php printf( '<a href="%s">%s</a>', admin_url( 'admin.php?page=gh_funnels&action=edit&funnel=' . $funnel->ID ), $funnel->title ); ?></td>
                <td class="summary-total"><?php echo $count ?></td>
            </tr>
            <?php

        endforeach;

        ?></tbody></table><?php

    }

}