<?php
namespace Groundhogg\Admin\Funnels;

use Groundhogg\Plugin;
use Groundhogg\Reporting\New_Reports\Base_Line_Chart_Report;
use Groundhogg\Reporting\New_Reports\Chart_Draw;
use Groundhogg\Reporting\New_Reports\Chart_Email_Activity;
use Groundhogg\Reports;

/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/23/2018
 * Time: 4:18 PM
 */

$line_chart = new Chart_Draw( 0, 0 );

$data = Plugin::$instance->admin->get_page( 'funnels' )->get_chart_data();
?>
<div id="funnel-chart" class="step-reporting" style="margin-bottom: 0;width: 100%;">
    <div class="inside" ">

    <div style="height: 400px;padding: 10px;">
        <canvas id="chart_funnel_details"></canvas>
    </div>
        <script type="text/javascript">

            (function ($) {
                var myChart;

                var chart = $('#chart_funnel_details');

                if (myChart != null) {
                    myChart.destroy();
                }

                var ctx = chart[0].getContext('2d');
                myChart = new Chart(ctx, {
                    "type": "line",
                    "data": {
                        "labels": <?php echo json_encode( $data ['label'] ); ?>,
                        "datasets": <?php  echo json_encode( [
							array_merge( [
								'label' => __( 'Completed Events', 'groundhogg' ),
								'data'  => $data['complete'],

							], $line_chart->get_line_style() ),
		                    array_merge( [
			                    'label' => __( 'waiting Events', 'groundhogg' ),
			                    'data'  => $data['waiting'],

		                    ], $line_chart->get_line_style() ),
						] );  ?>
                    },
                    "options": <?php echo json_encode( $line_chart->get_options() ); ?>
                });


            })(jQuery);


            // // draw Hover line in the graph
            // var draw_line = Chart.controllers.line.prototype.draw;
            // Chart.helpers.extend(Chart.controllers.line.prototype, {
            //     draw: function () {
            //         draw_line.apply(this, arguments);
            //         if (this.chart.tooltip._
            //         active && this.chart.tooltip._active.length) {
            //             var ap = this.chart.tooltip._active[0];
            //             var ctx = this.chart.ctx;
            //             var x = ap.tooltipPosition().x;
            //             var topy = this.chart.scales['y-axis-0'].top;
            //             var bottomy = this.chart.scales['y-axis-0'].bottom;
            //
            //             ctx.save();
            //             ctx.beginPath();
            //             ctx.moveTo(x, topy);
            //             ctx.lineTo(x, bottomy);
            //             ctx.lineWidth = 1;
            //             ctx.strokeStyle = '#727272';
            //             ctx.setLineDash([10, 10]);
            //             ctx.stroke();
            //             ctx.restore();
            //         }
            //     }
            //
            // });
            //
            // Chart.plugins.register({
            //     afterDraw: function (chart) {
            //         if (chart.data.datasets.length === 0) {
            //             // No data is present
            //             var ctx = chart.chart.ctx;
            //             var width = chart.chart.width;
            //             var height = chart.chart.height;
            //             chart.clear();
            //
            //             ctx.save();
            //             ctx.textAlign = 'center';
            //             ctx.textBaseline = 'middle';
            //             ctx.font = "16px normal 'Helvetica Nueue'";
            //             ctx.fillText('No data to display', width / 2, height / 2);
            //             ctx.restore();
            //         }
            //     }
            // });


            var FunnelChart = {};

            (function ($, chart) {

                var previousPoint = null, previousLabel = null;

                $.fn.UseTooltip = function () {
                    $(this).bind("plothover", function (event, pos, item) {
                        if (item) {
                            if ((previousLabel != item.series.label) || (previousPoint != item.dataIndex)) {
                                previousPoint = item.dataIndex;
                                previousLabel = item.series.label;
                                $("#tooltip").remove();

                                var x = item.datapoint[0];
                                var y = item.datapoint[1];

                                var color = item.series.color;
                                showTooltip(item.pageX, item.pageY, color, "<strong>" + item.series.label + "</strong>: " + y);
                            }
                        } else {
                            $("#tooltip").remove();
                            previousPoint = null;
                        }
                    });
                };

                function showTooltip(x, y, color, contents) {
                    $('<div id="tooltip">' + contents + '</div>').css({
                        position: 'absolute',
                        display: 'none',
                        top: y + 10,
                        left: x - 120,
                        border: '2px solid ' + color,
                        padding: '3px',
                        'font-size': '9px',
                        'border-radius': '5px',
                        'background-color': '#fff',
                        'font-family': 'Verdana, Arial, Helvetica, Tahoma, sans-serif',
                        opacity: 0.9
                    }).appendTo("body").fadeIn(200);
                }

                $.extend(chart, {

                    data: <?php echo wp_json_encode( Plugin::$instance->admin->get_page( 'funnels' )->get_chart_data() ); ?>,
                    options: {
                        series: {
                            lines: {show: true},
                            points: {
                                radius: 3,
                                show: true
                            }
                        },
                        grid: {
                            hoverable: true
                        },
                        xaxes: [{mode: 'categories'}],

                    },

                    init: function () {
                        this.draw();
                    },

                    draw: function () {

                        var $chart = $("#reporting-chart");

                        if ($chart.is(':visible')) {
                            $chart.plot(this.data, this.options);
                            $chart.UseTooltip();
                        }

                    }

                });

                $('#reporting-toggle').on('change', function (e) {
                    if ($(this).is(':checked') && $('#funnel-chart').is(':visible')) {
                        chart.draw();
                    }
                });

            })(jQuery, FunnelChart);
        </script>
        <div id="reporting-chart" style="width: auto;height: 250px"></div>
    </div>
</div>