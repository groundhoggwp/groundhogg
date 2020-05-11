<?php

namespace Groundhogg\Admin\Reports\Views;

use function Groundhogg\get_url_var;
use function Groundhogg\html;

echo html()->input( [
	'type'  => 'hidden',
	'name'  => 'email_id',
	'id'    => 'email_id',
	'class' => 'post-data',
	'value' => absint( get_url_var( 'email' ) )
] )

?>
<div class="groundhogg-chart-wrapper">
    <div class="groundhogg-chart">
        <h2 class="title"><?php _e( 'Email Stats', 'groundhogg' ); ?></h2>
        <div style="width: 100%; padding: ">
            <div class="float-left" style="width:60%">
                <canvas id="chart_donut_email_stats"></canvas>
            </div>
            <div class="float-left" style="width:40%">
                <div id="chart_donut_email_stats_legend" class="chart-legend"></div>
            </div>
        </div>
    </div>
    <div class="groundhogg-chart-no-padding">
        <h2 class="title"><?php _e( 'Email Stats', 'groundhogg' ); ?></h2>
        <div id="table_email_stats"></div>
    </div>
</div>
<div class="groundhogg-quick-stats">
    <div class="groundhogg-report">

		<?php quick_stat_report( [
			'id'    => 'total_emails_sent',
			'title' => __( 'Sent', 'groundhogg' ),
			'style' => 'width:33%;'
		] ); ?>

		<?php quick_stat_report( [
			'id'    => 'email_open_rate',
			'title' => __( 'Open Rate', 'groundhogg' ),
			'style' => 'width:33%;'
		] ); ?>

		<?php quick_stat_report( [
			'id'    => 'email_click_rate',
			'title' => __( 'Click Thru Rate', 'groundhogg' ),
			'style' => 'width:33%;'
		] ); ?>
    </div>
</div>
<div class="groundhogg-chart-wrapper">
    <div class="groundhogg-chart-no-padding full-width">
        <h2 class="title"><?php _e( 'Links Clicked', 'groundhogg' ); ?></h2>
        <div id="table_email_links_clicked"></div>
    </div>
</div
<div class="groundhogg-chart-wrapper">
    <div class="groundhogg-chart-no-padding full-width">
        <h2 class="title"><?php _e( 'Funnels', 'groundhogg' ); ?></h2>
        <div id="table_email_funnels_used_in"></div>
    </div>
</div