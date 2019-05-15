<?php
/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/27/2018
 * Time: 9:13 AM
 */

class WPGH_Time_Range_Widget extends WPGH_Reporting_Widget
{

    /**
     * WPGH_Report_V2 constructor.
     */
    public function __construct()
    {
        $this->wid = 'time_range_widget';
        $this->name = _x( 'Reporting Time Range', 'widget_name', 'groundhogg' );

        parent::__construct();
    }

    public function widget()
    {
        ?>
        <form action="" method="get">
            <div class="actions">
                <?php $args = array(
                    'name'      => 'date_range',
                    'id'        => 'date_range',
                    'options'   => array(
                        'today'         => _x( 'Today', 'reporting_range', 'groundhogg' ),
                        'yesterday'     => _x( 'Yesterday', 'reporting_range', 'groundhogg' ),
                        'this_week'     => _x( 'This Week', 'reporting_range', 'groundhogg' ),
                        'last_week'     => _x( 'Last Week', 'reporting_range', 'groundhogg' ),
                        'last_30'       => _x( 'Last 30 Days', 'reporting_range', 'groundhogg' ),
                        'this_month'    => _x( 'This Month', 'reporting_range', 'groundhogg' ),
                        'last_month'    => _x( 'Last Month', 'reporting_range', 'groundhogg' ),
                        'this_quarter'  => _x( 'This Quarter', 'reporting_range', 'groundhogg' ),
                        'last_quarter'  => _x( 'Last Quarter', 'reporting_range', 'groundhogg' ),
                        'this_year'     => _x( 'This Year', 'reporting_range', 'groundhogg' ),
                        'last_year'     => _x( 'Last Year', 'reporting_range', 'groundhogg' ),
                        'custom'        => _x( 'Custom Range', 'reporting_range', 'groundhogg' ),
                    ),
                    'selected' => $this->get_url_var( 'date_range', 'this_week' ),
                ); echo WPGH()->html->dropdown( $args );

                submit_button( __( 'Refresh', 'groundhogg' ), 'secondary', 'change_reporting', false );

                $class = $this->get_url_var( 'date_range' ) === 'custom' ? '' : 'hidden';

                ?><div class="custom-range <?php echo $class ?>"><hr><?php

                echo WPGH()->html->date_picker(array(
                    'name'  => 'custom_date_range_start',
                    'id'    => 'custom_date_range_start',
                    'class' => 'input',
                    'value' => $this->get_url_var( 'custom_date_range_start' ),
                    'attributes' => '',
                    'placeholder' => 'YYY-MM-DD',
                    'min-date' => date( 'Y-m-d', strtotime( '-100 years' ) ),
                    'max-date' => date( 'Y-m-d', strtotime( '+100 years' ) ),
                    'format' => 'yy-mm-dd'
                ));
                echo WPGH()->html->date_picker(array(
                    'name'  => 'custom_date_range_end',
                    'id'    => 'custom_date_range_end',
                    'class' => 'input',
                    'value' => $this->get_url_var( 'custom_date_range_end' ),
                    'attributes' => '',
                    'placeholder' => 'YYY-MM-DD',
                    'min-date' => date( 'Y-m-d', strtotime( '-100 years' ) ),
                    'max-date' => date( 'Y-m-d', strtotime( '+100 years' ) ),
                    'format' => 'yy-mm-dd'
                )); ?>
                </div>
                <script>jQuery(function($){$('#date_range').change(function(){
                        if($(this).val() === 'custom'){
                            $('.custom-range').removeClass('hidden');
                        } else {
                            $('.custom-range').addClass('hidden');
                        }})});
                </script>
            </div>
        </form>
        <p class="description"><?php _e( 'Use this form to quickly change the reporting time for any Groundhogg reporting that use it.', 'groundhogg' ); ?></p>
<?php
    }
}