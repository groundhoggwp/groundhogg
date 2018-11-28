<?php
/**
 * This is the page which allows users to view reports related to sent broadcasts.
 *
 * To add reporting to the screen, you can output any HTML with the action 'wpgh_broadcast_reporting_after'
 *
 * @package     Admin
 * @subpackage  Admin/Broadcasts
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @see         WPGH_Broadcasts_Page::report()
 * @since       File available since Release 0.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$id = intval( $_GET[ 'broadcast' ] );

$broadcast = new WPGH_Broadcast( $id );

if ( $broadcast->status !== 'sent' ):

    _e( 'Stats will show once the broadcast has been sent.', 'groundhogg' );

else:


    ?>
<h2><?php _e( 'Stats', 'groundhogg'  ); ?></h2>
<table class="form-table">

    <tbody>


    <tr>
        <th><?php  _e( 'Total Delivered', 'groundhogg' ); ?></th>
        <td><?php

            $contact_sum = WPGH()->events->count( array(
                'funnel_id'     => WPGH_BROADCAST,
                'step_id'       => $broadcast->ID
            ) );

            echo sprintf( "<strong><a href='%s' target='_blank' >%d</a></strong></strong>",
                admin_url( sprintf( 'admin.php?page=gh_contacts&view=report&funnel=%s&step=%s&start=%s&end=%s', WPGH_BROADCAST, $broadcast->ID, 0, time() ) ),
                $contact_sum
            );


            ?>
        </td>
    </tr>
    <tr>
        <th><?php _e( 'Opens', 'groundhogg' ); ?></th>
        <td><?php

            $opens = WPGH()->activity->count( array(
                'funnel_id'     => WPGH_BROADCAST,
                'step_id'       => $broadcast->ID,
                'activity_type' => 'email_opened'
            ) );

                echo sprintf( "<strong><a href='%s' target='_blank' >%d (%d%%)</a></strong>",
                admin_url( sprintf( 'admin.php?page=gh_contacts&view=activity&funnel=%s&step=%s&activity_type=%s&start=%s&end=%s', WPGH_BROADCAST, $broadcast->ID, 'email_opened', 0, time() ) ),
                $opens,
                ( $opens / $contact_sum ) * 100
            );

            ?></td>
    </tr>
    <tr>
        <th><?php _e( 'Clicks', 'groundhogg' ); ?></th>
        <td><?php
            $clicks = WPGH()->activity->count( array(
                'funnel_id'     => WPGH_BROADCAST,
                'step_id'       => $broadcast->ID,
                'activity_type' => 'email_link_click'
            ) );

            echo sprintf( "<strong><a href='%s' target='_blank' >%d (%d%%)</a></strong>",
                admin_url( sprintf( 'admin.php?page=gh_contacts&view=activity&funnel=%s&step=%s&activity_type=%s&start=%s&end=%s', WPGH_BROADCAST, $broadcast->ID, 'email_link_click', 0, time() ) ),
                $clicks,
                ( $clicks / $contact_sum ) * 100
            );

            ?></td>
    </tr>
    <tr>
        <th><?php _e( 'Click Through Rate', 'groundhogg' ); ?></th>
        <td><?php echo sprintf("<strong>%d%%</strong>", ( $clicks / $opens ) * 100 ); ?></td>
    </tr>
    <tr>
        <th><?php _e( 'Unopened', 'groundhogg' ); ?></th>
        <td><?php echo sprintf("<strong>%d (%d%%)</strong>", $contact_sum - $opens,  ( ( $contact_sum - $opens ) / $contact_sum ) * 100 ); ?></td>

    </tr>

    <?php

        /*
        * create array  of data ..
        */

        $dataset  =  array();

        $dataset[] = array(
            'label' => __('Opens Not clicked'),
            'data' => $opens - $clicks,
            'url'  => admin_url( sprintf( 'admin.php?page=gh_contacts&view=activity&funnel=%s&step=%s&activity_type=%s&start=%s&end=%s', WPGH_BROADCAST, $broadcast->ID, 'email_opened', 0, time() ) ),
        ) ;
        $dataset[] = array(
            'label' => __('Opens And Clicked'),
            'data' => $clicks,
            'url'  => admin_url( sprintf( 'admin.php?page=gh_contacts&view=activity&funnel=%s&step=%s&activity_type=%s&start=%s&end=%s', WPGH_BROADCAST, $broadcast->ID, 'email_link_click', 0, time() ) )
        ) ;
        $dataset[] = array(
            'label' => __('Unopened'),
            'data' => $contact_sum - $opens,
            'url'  => '#'

        ) ;

    ?>

    <tr  colspan="2">
        <script type="text/javascript" >
            jQuery(function($) {
                var dataSet = <?php echo json_encode($dataset)?>;
                $.plot('#placeholder', dataSet, {
                    grid : {
                        clickable : true,
                        hoverable : true
                    },
                    series: {
                        pie: {
                            innerRadius : 0.5,
                            show: true,
                            label: {
                                show:true,
                                radius: 0.8,
                                formatter: function (label, series) {
                                    return '<div style="border:1px solid grey;font-size:8pt;text-align:center;padding:5px;color:white;">' +
                                        label + ' : ' +
                                        Math.round(series.percent) +
                                        '%</div>';
                                },
                                background: {
                                    opacity: 0.8,
                                    color: '#000'
                                }
                            },
                        }
                    }
                });

                $('#placeholder').bind("plotclick", function(event,pos,obj) {
                    window.location.replace(dataSet[obj.seriesIndex].url);
                });
            });

        </script>
        <div id="placeholder" style="width:400px;height:300px"></div>

    </tr>

    </tbody>

</table>

<h2><?php _e( 'Links Clicked', 'groundhogg'  ); ?></h2>
<?php

    $activity = WPGH()->activity->get_activity( array(
        'funnel_id'     => WPGH_BROADCAST,
        'step_id'       => $broadcast->ID,
        'activity_type' => 'email_link_click'
    ) );


    $links = array();

    foreach ( $activity as $event ){

        if ( isset( $links[ $event->referer ] ) ){
            $links[ $event->referer ] += 1;
        } else {
            $links[ $event->referer ] = 1;
        }

    }

    ?>
    <table class="wp-list-table widefat fixed striped" style="max-width: 700px;">
    <thead>
        <tr>
            <th><?php _e( 'Link' ); ?></th>
            <th><?php _e( 'Clicks' ); ?></th>
        </tr>
    </thead><tbody><?php

    if ( empty( $links ) ){

        ?>
        <tr>
            <td colspan="2"><?php _e( 'No Links Clicked...', 'groundhogg' ); ?></td>
        </tr>
        <?php

    }

    foreach ( $links as $link => $clicks ):
    ?>
    <tr>
        <td><?php echo sprintf( "<a href='%s' target='_blank' >%s</a>", $link, $link ) ?></td>
        <td><?php echo sprintf( "<a href='%s' target='_blank' >%d</a>", admin_url( sprintf( 'admin.php?page=gh_contacts&view=activity&funnel=%s&step=%s&activity_type=%s&referer=%s&start=%s&end=%s', WPGH_BROADCAST, $broadcast->ID, 'email_link_click', $link, 0, time() ) ), $clicks ); ?></td>
    </tr>
    <?php
    endforeach;
    ?>





    </tbody></table><?php
endif; ?>

