<?php

namespace Groundhogg\Admin\Reports\Views;

// Overview

use Groundhogg\Classes\Activity;
use Groundhogg\Funnel;
use Groundhogg\Plugin;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use function Groundhogg\html;

function get_funnel_id() {
	if ( get_request_var( 'funnel' ) ) {
		return absint( get_request_var( 'funnel' ) );
	}

	return Plugin::$instance->reporting->get_report( 'complete_funnel_activity' )->get_funnel_id();
}

$funnels = get_db( 'funnels' );
$funnels = $funnels->query( [ 'status' => 'active' ] );

$options = [];

foreach ( $funnels as $funnel ) {
	$funnel                       = new Funnel( absint( $funnel->ID ) );
	$options[ $funnel->get_id() ] = $funnel->get_title();
}


function quick_stat_report( $args = [] ) {

	$args = wp_parse_args( $args, [
		'id'    => uniqid( 'groundhogg_' ),
		'title' => 'Report',
		'info'  => 'Some interesting data...',
		'style' => ''
	] );

	?>

    <div class="groundhogg-quick-stat" id="<?php esc_attr_e( $args[ 'id' ] ); ?>"
         style="<?php esc_attr_e( $args[ 'style' ] ); ?>">
        <div class="groundhogg-quick-stat-title"><?php esc_html_e( $args[ 'title' ] ) ?></div>
        <div class="groundhogg-quick-stat-info"></div>
        <div class="groundhogg-quick-stat-number">1234</div>
        <div class="groundhogg-quick-stat-previous green">
            <span class="groundhogg-quick-stat-arrow up"></span>
            <span class="groundhogg-quick-stat-prev-percent">25%</span>
        </div>
        <div class="groundhogg-quick-stat-compare">vs. Previous 30 Days</div>
    </div>
	<?php
}

?>
<div class="actions" style="margin-bottom: 25px; float: right">

	<?php
	$args = array(
		'name'        => 'funnel-id',
		'id'          => 'funnel-id',
		'options'     => $options,
		'selected'    => [ get_funnel_id() ],
		'option_none' => false,
	);
	echo Plugin::$instance->utils->html->dropdown( $args );
	?>
</div>
<div style="clear: both;"></div>

<div class="groundhogg-report">
    <h2 class="title"><?php _e( 'Funnel Breakdown', 'groundhogg' ); ?></h2>
    <div style="height: 400px">
        <canvas id="chart_funnel_breakdown"></canvas>
    </div>
</div>
<div class="groundhogg-quick-stats">
    <div class="groundhogg-report">

		<?php quick_stat_report( [
			'id'    => 'total_contacts_in_funnel',
			'title' => __( 'Active Contacts', 'groundhogg' )
		] ); ?>

		<?php quick_stat_report( [
			'id'    => 'total_funnel_conversion_rate',
			'title' => __( 'Funnel Conversion Rate', 'groundhogg' ),
		] ); ?>

		<?php quick_stat_report( [
			'id'    => 'total_benchmark_conversion_rate',
			'title' => __( 'Benchmark Conversion Rate', 'groundhogg' ),
		] ); ?>

		<?php quick_stat_report( [
			'id'    => 'total_abandonment_rate',
			'title' => __( 'Abandonment Rate', 'groundhogg' ),
		] );
		?>
    </div>
</div>

<div class="groundhogg-chart-wrapper">
    <div class="groundhogg-chart-no-padding">
        <h2 class="title"><?php _e( 'Top Performing Emails in Funnel', 'groundhogg' ); ?></h2>
        <div id="table_top_performing_emails"></div>
    </div>
    <div class="groundhogg-chart-no-padding">
        <h2 class="title"><?php _e( 'Benchmark Conversion Rate', 'groundhogg' ); ?></h2>
        <div id="table_benchmark_conversion_rate"></div>
    </div>
</div>

<!--<div class="groundhogg-chart-wrapper">-->
<!--    <div class="groundhogg-chart-no-padding">-->
<!--        <h2 class="title"> title </h2>-->
<!--    </div>-->
<!--    <div class="groundhogg-chart-no-padding">-->
<!--        <h2 class="title">title</h2>-->
<!--    </div>-->
<!--</div>-->