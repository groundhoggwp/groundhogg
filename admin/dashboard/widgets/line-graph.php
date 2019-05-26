<?php
namespace Groundhogg\Admin\Dashboard\Widgets;

use function Groundhogg\isset_not_empty;
use Groundhogg\Plugin;

/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/27/2018
 * Time: 9:13 AM
 */

abstract class Line_Graph extends Dashboard_Widget
{

    protected $dataset = [];

    /**
     * @return string
     */
    abstract public function get_mode();

    /**
     * @return bool
     */
    protected function hide_if_no_data()
    {
        return true;
    }

    /**
     * Output the widget HTML
     */
    public function widget()
    {
        /*
         * Get Data from the Override method.
         */
        $data = $this->get_data();

        $is_empty = empty( $data );

        if ( ! $is_empty ){

            $datasum = [];

            // Check all datasets for data.
            foreach ( $data as $i => $dataset ){
                $datasum[] = array_sum( wp_list_pluck( $dataset['data'], 1 ) );
            }

            if ( array_sum( $datasum ) === 0 ){
                $is_empty = true;
            }
        }

        // Show anyway
        if ( ! $this->hide_if_no_data() && $is_empty ){
            $is_empty = false;
        }

        if ( ! $is_empty ):

            if ( is_array( $data ) ){
                $data = wp_json_encode( $data );
            }

            ?>
            <div class="report">
                <script type="text/javascript">
                    jQuery(function($) {

                        /* DATA OPERATION */
                        var dataset = <?php echo $data; ?>;
                        var options = {
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
                            xaxis: {
                                mode: "<?php echo $this->get_mode(); ?>",
                                localTimezone: true
                            }
                        };

                        /* TOOL TIP */
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
                                        var date =  new Date(x).toDateString();
                                        showTooltip(item.pageX, item.pageY, color, "<strong>" + item.series.label + "</strong>: " + y + "<br/>" + date );
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
                                top: y - 40,
                                left: x - 120,
                                border: '2px solid ' + color,
                                padding: '3px',
                                'font-size': '9px',
                                'border-radius': '5px',
                                'background-color': '#fff',
                                'font-family': 'Verdana, Arial, Helvetica, Tahoma, sans-serif',
                                'z-index': 999999999999999999999999,
                                opacity: 0.9
                            }).appendTo("body").fadeIn(200);
                        }

                        /* PLOT CHART */

                        if ( $( "#graph-<?php echo $this->get_id(); ?>" ).width() > 0 ){
                            $.plot($("#graph-<?php echo $this->get_id(); ?>"), dataset, options);
                            $("#graph-<?php echo $this->get_id(); ?>").UseTooltip();
                        }

                    });
                </script>
                <div id="graph-<?php echo $this->get_id(); ?>" style="width:auto;height: 250px;"></div>
            </div>
            <?php

        $this->extra_widget_info();

        else:

            echo Plugin::$instance->utils->html->description( __( 'No data to show yet.', 'groundhogg' ) );

        endif;
    }

    /**
     * Any additional information needed for the widget.
     *
     * @return void
     */
    abstract protected function extra_widget_info();

    /**
     * Return several reports used rather than just 1.
     *
     * @return string[]
     */
    abstract protected function get_report_ids();

    /**
     * @return array
     */
    public function get_data()
    {
        $data = [];

        foreach ( $this->get_report_ids() as $report_id ){

            $report = Plugin::$instance->reporting->get_report( $report_id );

            if ( $report && method_exists( $this, 'normalize_' . $report_id ) ){
                $data[] = [
                    'label' => $report->get_name(),
                    'data'  => call_user_func( [ $this, 'normalize_' . $report_id ], $report->get_data() )
                ];
            }

        }

        $this->dataset = $data;

        return $data;
    }
}