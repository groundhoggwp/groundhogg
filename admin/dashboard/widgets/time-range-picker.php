<?php
namespace Groundhogg\Admin\Dashboard\Widgets;

use function Groundhogg\get_request_var;
use Groundhogg\Plugin;

/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/27/2018
 * Time: 9:13 AM
 */

class Time_Range_Picker extends Dashboard_Widget
{

    public function widget()
    {
        $html = Plugin::$instance->utils->html;

        ?>
        <form action="" method="get">
            <div class="actions">
                <?php $args = array(
                    'name'      => 'range',
                    'id'        => 'date_range',
                    'options'   => Plugin::$instance->reporting->get_reporting_ranges(),
                    'selected'  => Plugin::$instance->reporting->get_range(),
                ); echo $html->dropdown( $args );

                submit_button( __( 'Refresh', 'groundhogg' ), 'secondary', 'change_reporting', false );

                $class = Plugin::$instance->reporting->get_range() === 'custom' ? '' : 'hidden';

                ?><div class="custom-range <?php echo $class ?>"><hr><?php

                echo $html->date_picker(array(
                    'name'  => 'custom_date_range_start',
                    'id'    => 'custom_date_range_start',
                    'class' => 'input',
                    'value' => get_request_var( 'custom_date_range_start' ),
                    'attributes' => '',
                    'placeholder' => 'YYY-MM-DD',
                    'min-date' => date( 'Y-m-d', strtotime( '-100 years' ) ),
                    'max-date' => date( 'Y-m-d', strtotime( '+100 years' ) ),
                    'format' => 'yy-mm-dd'
                ));
                echo $html->date_picker(array(
                    'name'  => 'custom_date_range_end',
                    'id'    => 'custom_date_range_end',
                    'class' => 'input',
                    'value' => get_request_var( 'custom_date_range_end' ),
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

    public function get_id()
    {
        return 'time_range';
    }

    public function get_name()
    {
        return __( 'Time Range', 'groundhogg' );
    }
}