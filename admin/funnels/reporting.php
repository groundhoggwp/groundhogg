<?php
namespace Groundhogg\Admin\Funnels;

use Groundhogg\Plugin;
/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/23/2018
 * Time: 4:18 PM
 */

?>
<div id="funnel-chart" class="postbox hidden step-reporting" style="margin-bottom: 0;">
    <div class="inside">
        <script type="text/javascript">

            var FunnelChart = {};

            (function ($,chart) {

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
                                showTooltip(item.pageX, item.pageY, color, "<strong>" + item.series.label + "</strong>: " + y );
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

                $.extend( chart, {

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
                        xaxes: [ { mode: 'categories' } ],

                    },

                    init: function()
                    {
                        this.draw();
                    },

                    draw : function () {

                        var $chart = $("#reporting-chart");

                        $chart.plot( this.data, this.options);
                        $chart.UseTooltip();

                    }

                } );

                $( '#reporting-toggle' ).on( 'change', function (e) {

                    if ( $(this).is( ':checked' ) && ! $( '#funnel-chart' ).hasClass( 'hidden' ) ){

                        chart.draw();

                    }

                } );

            })(jQuery, FunnelChart);
        </script>
        <div id="reporting-chart" style="width: auto;height: 250px"></div>
    </div>
</div>