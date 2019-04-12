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
        $this->name = _x( 'Most Active Funnels', 'widget_name', 'groundhogg' );

        parent::__construct();
    }

    public function widget()
    {

        /*Get all the funnels */
        $funnels = WPGH()->funnels->get_funnels( array( 'status' => 'active' ) );

        if ( empty( $funnels ) ){
            printf( '<p>%s</p>', _x( 'You have no active funnels.', 'notice', 'groundhogg' ) );
            return;
        }

        /* Num events to $funnel */
        $ordered = array();

        /* Get all events within time range */
        foreach ( $funnels as $funnel ){

            $query = new WPGH_Contact_Query();
            $contacts = $query->query( array(
                'report' => array(
                    'start' => $this->start_time,
                    'end'   => $this->end_time,
                    'funnel' => $funnel->ID
                )
            ) );

            $num_contacts = count( $contacts );
            $ordered[ $num_contacts ] = $funnel;

        }

        ksort( $ordered );
        $ordered = array_reverse( $ordered, true );

//        if ( count( $ordered ) > 5 ){
//            $ordered = array_slice( $ordered, 0, 5 );
//        }

        ?><table class="chart-summary">
        <thead>
        <tr>
            <th><?php _ex( 'Funnel', 'column_title', 'groundhogg' ); ?></th>
            <th><?php _ex( 'Active Contacts', 'column_title', 'groundhogg' ); ?></th>
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