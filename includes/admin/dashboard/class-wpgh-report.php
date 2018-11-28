<?php
/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/27/2018
 * Time: 9:13 AM
 */

class WPGH_Report
{

    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $name;

    /**
     * @var int
     */
    public $start_time;

    /**
     * @var int
     */
    public $end_time;

    /**
     * @var int
     */
    public $start_range;

    /**
     * @var int
     */
    public $end_range;

    /**
     * @var string
     */
    public $range;

    /**
     * @var int;
     */
    public $points;

    /**
     * @var int
     */
    public $difference;


    public function __construct($title , $name)
    {
        $this->name         = $name;
        $this->title        = $title;
        $this->range        = WPGH()->menu->dashboard->get_reporting_range();

        switch ( $this->range ){
            default:
            case 'last_24';
                $this->start_time   = wpgh_round_to_hour( WPGH()->menu->dashboard->reporting_start_time );
                $this->end_time     = wpgh_round_to_hour( WPGH()->menu->dashboard->reporting_end_time );
                $this->points = 24;
                $this->difference = HOUR_IN_SECONDS;
                break;
            case 'last_7';
                $this->start_time   = wpgh_round_to_day( WPGH()->menu->dashboard->reporting_start_time );
                $this->end_time     = wpgh_round_to_day( WPGH()->menu->dashboard->reporting_end_time );
                $this->points = 7;
                $this->difference = DAY_IN_SECONDS;
                break;
            case 'last_30';
                $this->start_time   = wpgh_round_to_day( WPGH()->menu->dashboard->reporting_start_time );
                $this->end_time     = wpgh_round_to_day( WPGH()->menu->dashboard->reporting_end_time );
                $this->points = 30;
                $this->difference = DAY_IN_SECONDS;
                break;
            case 'custom';
                $this->start_time   = wpgh_round_to_day( WPGH()->menu->dashboard->reporting_start_time );
                $this->end_time     = wpgh_round_to_day( WPGH()->menu->dashboard->reporting_end_time );
                $this->points = ( $this->end_time - $this->start_time ) / DAY_IN_SECONDS;
                $this->difference = DAY_IN_SECONDS;
                break;

        }

        $this->start_range  = $this->start_time;
        $this->end_range = $this->start_range + $this->difference;

        add_filter('wpgh_dashboard_graphs', array($this, 'register'));

    }

    public function register($graphs)
    {
        $graphs[] = $this;

        return $graphs;
    }


    public function display()
    {
        /*
         * Get Data from the Override method.
         */

        $jsondata  = $this->get_data();

        ?>
        <div class="postbox" style="width: 48%;display: inline-block;">
            <h2><?php echo  $this->title ; ?></h2>
            <div class="inside">
                <script type="text/javascript">
                    jQuery(function($) {

                        /* DATA OPERATION */
                        var dataset = <?php echo $jsondata; ?>;
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
                                mode: "time",
                                // tickSize: [3, "day"],
                                // tickLength: 0,
                                // axisLabel: "Date",
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
                                        //var date1 =  new Date(x).getMonth();

                                        var unit = "";


                                        showTooltip(item.pageX, item.pageY, color,
                                            "<strong>" + item.series.label + "</strong>: " + y +
                                            "<br/>" + date);
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
                                opacity: 0.9
                            }).appendTo("body").fadeIn(200);
                        }

                        /* PLOT CHART */
                        $.plot($("#graph-<?php echo sanitize_key($this->name); ?>"), dataset, options);
                        $("#graph-<?php echo sanitize_key($this->name); ?>").UseTooltip();

                    });
                </script>

                <div id="graph-<?php echo sanitize_key($this->name); ?>" style="width: auto;height: 250px"></div>
           </div>
        </div>
    <?php
    }

    public function get_data()
    {
        // needs to over ride
        return array();

    }
}