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
     * @var string
     */
    public $range;


    public function __construct($title , $name)
    {
        $this->name         = $name;
        $this->title        = $title;
        $this->start_time   = wpgh_round_to_hour( WPGH()->menu->dashboard->reporting_start_time );
        $this->end_time     = wpgh_round_to_hour( WPGH()->menu->dashboard->reporting_end_time );
        $this->range        = WPGH()->menu->dashboard->get_reporting_range();

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
                        $.plot($("#graph-<?php echo sanitize_key($this->name); ?>"), dataset, options);
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