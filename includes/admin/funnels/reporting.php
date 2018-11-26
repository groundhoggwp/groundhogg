<?php
/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/23/2018
 * Time: 4:18 PM
 */

?>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">

    (function ($) {
        $( '#reporting-toggle' ).on( 'change', function (e) {
            if ( $(this).is( ':checked' ) && ! wpghFunnelEditor.reportData ){
                drawChart();
            }
        } );
    })(jQuery);

    google.charts.load('current', {'packages':['corechart', 'line']});
    // google.charts.setOnLoadCallback( drawChart );

    function drawChart() {
        var data = google.visualization.arrayToDataTable([
            ['Event', 'Number Of Contacts'],
            <?php
            /* Pass funnel ID to get Steps */
            $steps = WPGH()->steps->get_steps( array(
                'funnel_id' => $funnel_id
            ) );
            foreach ( $steps as $step ) {
                $query = new WPGH_Contact_Query();
                $args = array(
                    'report' => array(
                        'funnel' => $funnel_id,
                        'step' => $step->ID,
                        'status' => 'complete',
                        'start' => WPGH()->menu->funnels_page->reporting_start_time,
                        'end' => WPGH()->menu->funnels_page->reporting_end_time,
                    )
                );
                $count = count($query->query($args));
                //echo '['. $count . ' , "'.$step->step_title.'"],';
                echo  '["'.$step->step_title.'",'.$count.'],';
            }
            ?>
        ]);
        var options = {
            title: 'Funnel Report',
            // curveType: 'function',
            // legend: { position: 'left' }
        };

        var chart = new google.visualization.LineChart(document.getElementById('curve_chart'));

        chart.draw(data, options);
    }
</script>
<div id="curve_chart" class="step-reporting hidden" style="height: 370px;"></div>
