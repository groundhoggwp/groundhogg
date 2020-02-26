<?php

namespace Groundhogg\Admin\Dashboard\Widgets;

use function Groundhogg\isset_not_empty;
use Groundhogg\Plugin;

abstract class Line_Graph extends Dashboard_Widget {

	protected $dataset = [];

	/**
	 * @return string
	 */
	abstract public function get_mode();

	/**
	 * @return bool
	 */
	protected function hide_if_no_data() {
		return true;
	}

	/**
	 * Output the widget HTML
	 */
	public function widget() {
		/*
		 * Get Data from the Override method.
		 */
		$data = $this->get_data();

		$data_chart = $this->get_data_chart();

		$is_empty = empty( $data );

		if ( ! $is_empty ) {

			$datasum = [];

			// Check all datasets for data.
			foreach ( $data as $i => $dataset ) {
				$datasum[] = array_sum( wp_list_pluck( $dataset[ 'data' ], 1 ) );
			}

			if ( array_sum( $datasum ) === 0 ) {
				$is_empty = true;
			}
		}

		// Show anyway
		if ( ! $this->hide_if_no_data() && $is_empty ) {
			$is_empty = false;
		}

		if ( ! $is_empty ):



			foreach ( $data as $d ) {
				$label = 'a';
			}


			if ( is_array( $data ) ) {
				$data = wp_json_encode( $data );
			}

			if ( is_array( $data_chart ) ) {
				$data_chart = wp_json_encode( $data_chart );
			}


			?>
            <div class="report">
                <script type="text/javascript">
                    jQuery(function ($) {

                        /* DATA OPERATION */
                        var dataset = <?php echo $data; ?>;
                        console.log(dataset);
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

						<?php if ( $this->get_mode() === 'time' ): ?>

						<?php $offset = intval( get_option( 'gmt_offset' ) ) * HOUR_IN_SECONDS * 1000; ?>
                        $.fn.UseTooltipTime = function () {
                            $(this).bind("plothover", function (event, pos, item) {
                                if (item) {
                                    if ((previousLabel != item.series.label) || (previousPoint != item.dataIndex)) {
                                        previousPoint = item.dataIndex;
                                        previousLabel = item.series.label;
                                        $("#tooltip").remove();
                                        var x = item.datapoint[0];
                                        var y = item.datapoint[1];
                                        var color = item.series.color;
                                        var date = new Date(x - <?php echo $offset; ?> );
                                        showTooltip(item.pageX, item.pageY, color, "<strong>" + item.series.label + "</strong>: " + y + "<br/>" + date.toDateString());
                                    }
                                } else {
                                    $("#tooltip").remove();
                                    previousPoint = null;
                                }
                            });
                        };

						<?php else: ?>

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
                                        showTooltip(item.pageX, item.pageY, color, "<strong>" + item.series.label + "</strong>: " + y + "<br/>");
                                    }
                                } else {
                                    $("#tooltip").remove();
                                    previousPoint = null;
                                }
                            });
                        };

						<?php endif; ?>

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
                                'z-index': '99999',
                                opacity: 0.9
                            }).appendTo("body").fadeIn(200);
                        }

                        /* PLOT CHART */

                        function draw() {
                            if ($("#graph-<?php echo $this->get_id(); ?>").width() > 0) {
                                $.plot($("#graph-<?php echo $this->get_id(); ?>"), dataset, options);

								<?php if ( $this->get_mode() === 'time' ): ?>
                                $("#graph-<?php echo $this->get_id(); ?>").UseTooltipTime();
								<?php else: ?>
                                $("#graph-<?php echo $this->get_id(); ?>").UseTooltip();
								<?php endif; ?>
                            }
                        }

                        setTimeout(draw, 500);

                    });
                </script>
                <div id="graph-<?php echo $this->get_id(); ?>" style="width:auto;height: 250px;"></div>


                <canvas id="chart-<?php echo $this->get_id(); ?>" style="width:auto;height: 250px;"></canvas>
                <script>

                    console.log(<?php echo $this->get_id(); ?>);
                    console.log(<?php echo $data_chart ;?> );

                    var ctx = document.getElementById('<?php echo 'chart-' . $this->get_id();  ?>').getContext('2d');
                    var myChart = new Chart(ctx, {
                        type: 'line',
                        data: <?php echo $data_chart; ?>,
                        options: {
                            scales: {
                                yAxes: [{
                                    ticks: {
                                        beginAtZero: true
                                    }
                                }]
                            }
                        }
                    });
                </script>

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
	public function get_data() {
		$data = [];

		foreach ( $this->get_report_ids() as $report_id ) {

			$report = Plugin::$instance->reporting->get_report( $report_id );

			if ( $report && method_exists( $this, 'normalize_' . $report_id ) ) {
				$data[] = [
					'label' => $report->get_name(),
					'data'  => call_user_func( [ $this, 'normalize_' . $report_id ], $report->get_data() )
				];
			}

		}

		$this->dataset = $data;

		return $data;
	}

	public function get_data_chart() {
		$data = [];

		foreach ( $this->get_report_ids() as $report_id ) {

			$report = Plugin::$instance->reporting->get_report( $report_id );

			if ( $report && method_exists( $this, 'normalize_' . $report_id ) ) {
				$data[] = [
					'label' => $report->get_name(),
					'data'  => call_user_func( [ $this, 'normalize_' . $report_id ], $report->get_data() )
				];
			}

		}

		$mydata = [];

		$label_chart = [];
		$label_chart = [];
		foreach ( $data[ 0 ][ 'data' ] as $item ) {
			$label_chart[] = $item[ 0 ];
		}

		foreach ( $data as $d ) {

			$values = [];

			foreach ( $d[ 'data' ] as $v ) {
				$values[] = [
                    'x' => $v[0],
                    'y' => $v[ 1 ]
                ];

			}


			$mydata[] = [
				'label' => $d[ 'label' ],
				'data'  => $values
			];

		}


		return [
            'labels' => $label_chart,
            'datasets'=>$mydata
        ];

	}


	public function get_chart_data() {
		return $this->get_data();
	}
}