<?php

namespace Groundhogg\Admin\Reports\Views;

use function Groundhogg\get_url_var;
use function Groundhogg\html;

echo html()->input( [
	'type'  => 'hidden',
	'name'  => 'email_id',
	'id'    => 'email_id',
	'value' => absint( get_url_var( 'email' ) )
] )

?>
<div class="groundhogg-chart-wrapper">
    <div class="groundhogg-chart">
        <h2 class="title"><?php _e( 'Email Stats', 'groundhogg' ); ?></h2>
        <div style="width: 100%; padding: ">
            <div class="float-left" style="width:60%">
                <canvas id="donut_chart_email_stats"></canvas>
            </div>
            <div class="float-left" style="width:40%">
                <div id="chart_last_broadcast_legend" class="chart-legend"></div>
            </div>
        </div>
    </div>
    <div class="groundhogg-chart-no-padding">
        <h2 class="title"><?php _e( 'Email Stats', 'groundhogg' ); ?></h2>
        <div id="table_email_stats"></div>
    </div>
</div>

<div class="groundhogg-chart-wrapper">
    <div class="groundhogg-chart-no-padding full-width">
        <h2 class="title"><?php _e( 'Links Clicked', 'groundhogg' ); ?></h2>
        <div id="table_broadcast_link_clicked"></div>
    </div>
</div
