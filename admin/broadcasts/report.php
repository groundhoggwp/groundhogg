<?php

namespace Groundhogg\Admin\Broadcasts;

use Groundhogg\Broadcast;
use Groundhogg\Plugin;

/**
 * This is the page which allows users to view reporting related to sent broadcasts.
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

$broadcast = new Broadcast( $id );

if ( ! $broadcast->is_sent() ):
    Plugin::$instance->notices->add( 'unsent', _x( 'Stats will show once the broadcast has been sent.', 'notice', 'groundhogg' ), 'warning' );
    Plugin::$instance->notices->notices();
?>
<p class="submit">
    <a href="javascript:history.go(-1)" class="button button-primary">Go Back</a>
</p>
<?php

else:

    ?>
<h2><?php _e( 'Stats', 'groundhogg'  ); ?></h2>
<table class="form-table">
    <tbody>
    <tr>
        <th><?php  _ex( 'Total Delivered', 'stats','groundhogg' ); ?></th>
        <td><?php


            $contact_sum = Plugin::$instance->dbs->get_db('events')->count([
                'funnel_id'     => $broadcast->get_funnel_id(),
                'step_id'       => $broadcast->get_id()
            ] );

            echo sprintf( "<strong><a href='%s' target='_blank' >%d</a></strong></strong>",
                admin_url( sprintf( 'admin.php?page=gh_contacts&view=report&funnel=%s&step=%s&start=%s&end=%s', $broadcast->get_funnel_id(), $broadcast->get_id(), 0, time() ) ),
                $contact_sum
            );

            ?>
        </td>
    </tr>
    <tr>
        <th><?php _ex( 'Opens', 'stats','groundhogg' ); ?></th>
        <td><?php

            $opens = Plugin::$instance->dbs->get_db('events')->count([
                'funnel_id'     => $broadcast->get_funnel_id(),
                'step_id'       => $broadcast->get_id(),
                'activity_type' => 'email_opened'
            ] );

                echo sprintf( "<strong><a href='%s' target='_blank' >%d (%d%%)</a></strong>",
                admin_url( sprintf( 'admin.php?page=gh_contacts&view=activity&funnel=%s&step=%s&activity_type=%s&start=%s&end=%s', $broadcast->get_funnel_id(), $broadcast->get_id(), 'email_opened', 0, time() ) ),
                $opens,
                ( $opens / $contact_sum ) * 100
            );

            ?></td>
    </tr>
    <tr>
        <th><?php _ex( 'Clicks', 'stats', 'groundhogg' ); ?></th>
        <td><?php
            $clicks = Plugin::$instance->dbs->get_db('events')->count([
                'funnel_id'     => $broadcast->get_funnel_id(),
                'step_id'       => $broadcast->get_id(),
                'activity_type' => 'email_link_click'
            ] );

            echo sprintf( "<strong><a href='%s' target='_blank' >%d (%d%%)</a></strong>",
                admin_url( sprintf( 'admin.php?page=gh_contacts&view=activity&funnel=%s&step=%s&activity_type=%s&start=%s&end=%s', $broadcast->get_funnel_id(), $broadcast->get_id(), 'email_link_click', 0, time() ) ),
                $clicks,
                ( $clicks / $contact_sum ) * 100
            );

            ?></td>
    </tr>
    <tr>
        <th><?php _ex( 'Click Through Rate', 'stats', 'groundhogg' ); ?></th>
        <td><?php echo sprintf("<strong>%d%%</strong>", ( $clicks / $opens ) * 100 ); ?></td>
    </tr>
    <tr>
        <th><?php _ex( 'Unopened', 'stats','groundhogg' ); ?></th>
        <td><?php echo sprintf("<strong>%d (%d%%)</strong>", $contact_sum - $opens,  ( ( $contact_sum - $opens ) / $contact_sum ) * 100 ); ?></td>
    </tr>

    <?php

        /*
        * create array  of data ..
        */
        $dataset  =  array();

        $dataset[] = array(
            'label' => _x('Opens, did not click', 'stats', 'groundhogg'),
            'data' => $opens - $clicks,
            'url'  => admin_url( sprintf( 'admin.php?page=gh_contacts&view=activity&funnel=%s&step=%s&activity_type=%s&start=%s&end=%s', $broadcast->get_funnel_id(), $broadcast->get_id(), 'email_opened', 0, time() ) ),
        ) ;
        $dataset[] = array(
            'label' => _x('Opens and clicked', 'stats', 'groundhogg'),
            'data' => $clicks,
            'url'  => admin_url( sprintf( 'admin.php?page=gh_contacts&view=activity&funnel=%s&step=%s&activity_type=%s&start=%s&end=%s', $broadcast->get_funnel_id(), $broadcast->get_id(), 'email_link_click', 0, time() ) )
        ) ;
        $dataset[] = array(
            'label' => _x('Unopened', 'stats', 'groundhogg'),
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

<h2><?php _ex( 'Links Clicked', 'stats', 'groundhogg'  ); ?></h2>
<?php


//    $activity = WPGH()->activity->get_activity( array(
//        'funnel_id'     => $broadcast->get_funnel_id(),
//        'step_id'       => $broadcast->get_id(),
//        'activity_type' => 'email_link_click'
//    ) ); todo check query for fetch

     $activity = Plugin::$instance->dbs->get_db('events')->query([
         'funnel_id'     => $broadcast->get_funnel_id(),
         'step_id'       => $broadcast->get_id(),
         'activity_type' => 'email_link_click'
     ] );



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
            <th><?php _ex( 'Link', 'column_label', 'groudhogg' ); ?></th>
            <th><?php _ex( 'Clicks', 'column_label', 'groudhogg' ); ?></th>
        </tr>
    </thead><tbody><?php

    if ( empty( $links ) ){

        ?>
        <tr>
            <td colspan="2"><?php _ex( 'No Links Clicked...', 'notice', 'groundhogg' ); ?></td>
        </tr>
        <?php

    }

    foreach ( $links as $link => $clicks ):
    ?>
    <tr>
        <td><?php echo sprintf( "<a href='%s' target='_blank' >%s</a>", $link, $link ) ?></td>
        <td><?php echo sprintf( "<a href='%s' target='_blank' >%d</a>", admin_url( sprintf( 'admin.php?page=gh_contacts&view=activity&funnel=%s&step=%s&activity_type=%s&referer=%s&start=%s&end=%s', $broadcast->get_funnel_id(), $broadcast->get_id(), 'email_link_click', $link, 0, time() ) ), $clicks ); ?></td>
    </tr>
    <?php
    endforeach;
    ?>

    </tbody></table><?php
endif; ?>

