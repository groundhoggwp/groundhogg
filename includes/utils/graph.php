<?php

namespace Groundhogg\Utils;

use Groundhogg\Plugin;
use function Groundhogg\get_array_var;
use function Groundhogg\html;
use function Groundhogg\isset_not_empty;

class Graph
{

    protected $id;

    /**
     * @var array
     */
    protected $args = [];

    /**
     * @var array
     */
    protected $data = [];

    /**
     * Chart constructor.
     * @param $id string ID of the chart
     * @param array $args args to configure the chart
     * @param array $data data to display
     */
    public function __construct( $id='', $args=[], $data=[] )
    {

        $this->id = sanitize_key( $id );

        $this->args = wp_parse_args( $args, [
            'mode' => 'time'
        ] );

        $this->data = $data;

        $this->enqueue_scripts();
    }

    /**
     * Get the scripts going
     */
    public function enqueue_scripts()
    {
       wp_enqueue_script( 'jquery-flot' );

       switch ( $this->mode )
       {
           default:
           case 'time':
               wp_enqueue_script( 'jquery-flot-time' );
               break;
           case 'categories':
               wp_enqueue_script( 'jquery-flot-categories' );
               break;
       }
    }

    public function get_id()
    {
        return $this->id;
    }

    public function get_data()
    {
        return $this->data;
    }

    public function has_data()
    {
        $is_empty = empty( $this->data );

        if ( ! $is_empty ){

            $datasum = [];

            // Check all datasets for data.
            foreach ( $this->data as $i => $dataset ){
                $datasum[] = array_sum( wp_list_pluck( $dataset['data'], 1 ) );
            }

            if ( array_sum( $datasum ) === 0 ){
                $is_empty = true;
            }
        }

        return ! $is_empty;
    }

    /**
     * @param $key
     * @return mixed
     */
    public function __get( $key )
    {
        return get_array_var( $this->data, $key );
    }

    /**
     * Render the chart
     */
    public function render()
    {

        if ( $this->has_data() ):

            if ( is_array( $this->data ) ){
                $data = wp_json_encode( $this->data );
            }

            ?>
            <div class="graph">
                <script type="text/javascript">
                    jQuery( function($) {

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
                                mode: "<?php echo $this->args['mode']; ?>",
                                // mode : 'time',
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

                        function draw() {
                            if ( $( "#graph-<?php echo $this->id; ?>" ).is( ':visible' ) ){
                                $.plot($("#graph-<?php echo $this->id; ?>"), dataset, options);
                                $("#graph-<?php echo $this->id; ?>").UseTooltip();
                            }
                        }

                        $( '.load-graph-<?php echo $this->id; ?>' ).click( draw );
                        $( document ).click( draw );

                        setTimeout( draw, 500 );

                    });
                </script>
                <div id="graph-<?php echo $this->id; ?>" style="height: 250px;"></div>
                <p>
                    <a class="load-graph-<?php echo $this->id; ?> button button-secondary" href="javascript:void(0)" onclick=""><?php _e( 'Load' ); ?></a>
                </p>
            </div>
            <?php
        else:
            echo html()->description( __( 'No data to show yet.', 'groundhogg' ) );
        endif;
    }

}
