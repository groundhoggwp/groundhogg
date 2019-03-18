<?php
/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/27/2018
 * Time: 9:13 AM
 */

abstract class WPGH_Circle_Graph_Report extends WPGH_Reporting_Widget
{

    /**
     * Enqueue chart scripts
     */
    public function scripts()
    {
        wp_enqueue_script( 'jquery-flot', WPGH_ASSETS_FOLDER . 'lib/flot/jquery.flot.min.js', array(), filemtime(WPGH_PLUGIN_DIR . 'assets/lib/flot/jquery.flot.min.js') );
        wp_enqueue_script( 'jquery-flot-pie', WPGH_ASSETS_FOLDER . '/lib/flot/jquery.flot.pie.js', array(), filemtime(WPGH_PLUGIN_DIR . 'assets/lib/flot/jquery.flot.pie.js') );
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

        if ( ! empty( $data ) ):

            if ( is_array( $data ) ){
                $data = json_encode( $data );
            }

        ?>
        <div class="report">
            <script type="text/javascript" >

                jQuery(function($) {
                    var dataSet = <?php echo $data; ?>;

                    var options = {
                        grid : {
                            clickable : true,
                            hoverable : true
                        },
                        series: {
                            pie: {
                                show: true,
                                // radius: 1,
                                label: {
                                    show: true,
                                    radius: 7/8,
                                    formatter: function (label, series) {
                                        return "<div style='font-size:8pt; text-align:center; padding:2px; color:white;'>" + label + ' (' + Math.round(series.percent) + "%)</div>";
                                    },
                                    background: {
                                        opacity: 0.5,
                                        color: '#000'
                                    }
                                }
                            }
                        },
                    };

                    if ( $( "#graph-<?php echo sanitize_key($this->name); ?>" ).width() > 0 ){

                        $.plot($("#graph-<?php echo sanitize_key($this->name); ?>"), dataSet, options);

                        $('#graph-<?php echo sanitize_key($this->name); ?>').bind("plotclick", function(event,pos,obj) {
                            try{
                                window.location.replace(dataSet[obj.seriesIndex].url);
                            } catch (e) {}
                        });

                    }
                });

            </script>
            <div id="graph-<?php echo sanitize_key($this->name); ?>" style="width:auto;height: 300px"></div>
       </div>
    <?php

        endif;

        echo $this->extra_widget_info();
    }

    /**
     * Output additional information
     *
     * @return string
     */
    protected function extra_widget_info()
    {
        return '';
    }

    /**
     * Get the graph information.
     *
     * @return array
     */
    protected function get_data()
    {
        // needs to over ride
        return array();

    }
}