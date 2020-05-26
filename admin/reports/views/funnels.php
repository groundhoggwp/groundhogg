<?php

namespace Groundhogg\Admin\Reports\Views;

// Overview
use Groundhogg\Funnel;
use Groundhogg\Plugin;
use function Groundhogg\get_cookie;
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

?>
<div class="actions" style="margin-bottom: 25px; float: right">
	<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
		<?php

		echo html()->input( [
			'type' => 'hidden',
			'name' => 'page',
			'value' => 'gh_funnels'
		] );

		echo html()->input( [
			'type' => 'hidden',
			'name' => 'action',
			'value' => 'edit'
		] );

		$args = array(
			'name'        => 'funnel',
			'id'          => 'funnel-id',
			'class'       => 'post-data',
			'options'     => $options,
			'selected'    => absint( get_request_var( 'funnel', get_cookie( 'gh_reporting_funnel_id' ) ) ),
			'option_none' => false,
		);

		echo html()->dropdown( $args );

		echo html()->e( 'button', [
			'type'  => 'submit',
			'class' => 'button'
		], __( 'View Funnel', 'groundhogg' ) );

		?>
	</form>
</div>
<div style="clear: both;"></div>

<div class="groundhogg-report">
    <h2 class="title"><?php _e( 'Funnel Breakdown', 'groundhogg' ); ?></h2>
    <div class="big-chart-wrap">
        <canvas id="chart_funnel_breakdown"></canvas>
    </div>
</div>
<div class="groundhogg-quick-stats">
    <div class="groundhogg-report">

		<?php quick_stat_report( [
			'id'    => 'total_contacts_in_funnel',
			'title' => __( 'Active Contacts', 'groundhogg' ),
			'style' => [ 'width' => '33%' ]
		] ); ?>

		<?php quick_stat_report( [
			'id'    => 'total_funnel_conversion_rate',
			'title' => __( 'Conversion Rate', 'groundhogg' ),
			'style' => [ 'width' => '33%' ]
		] ); ?>

<!--		--><?php //quick_stat_report( [
//			'id'    => 'total_benchmark_conversion_rate',
//			'title' => __( 'Benchmark Conversion Rate', 'groundhogg' ),
//		] ); ?>

		<?php quick_stat_report( [
			'id'    => 'total_abandonment_rate',
			'title' => __( 'Abandonment Rate', 'groundhogg' ),
			'style' => [ 'width' => '33%' ]
		] );
		?>
    </div>
</div>

<div class="groundhogg-chart-wrapper">
    <div class="groundhogg-chart-no-padding">
        <h2 class="title"><?php _e( 'Top Performing Emails in Funnel', 'groundhogg' ); ?></h2>
        <div id="table_top_performing_emails" class="emails-list"></div>
    </div>
    <div class="groundhogg-chart-no-padding">
        <h2 class="title"><?php _e( 'Emails Needing Improvement', 'groundhogg' ); ?></h2>
        <div id="table_worst_performing_emails" class="emails-list"></div>
    </div>
</div>

<div class="groundhogg-chart-wrapper">
    <div class="groundhogg-chart-no-padding full-width">
        <h2 class="title"><?php _e( 'Benchmark Conversion Rate', 'groundhogg' ); ?></h2>
        <div id="table_benchmark_conversion_rate"></div>
    </div>
</div>
<div class="groundhogg-chart-wrapper">
    <div class="groundhogg-chart-no-padding full-width">
        <h2 class="title"><?php _e( 'Forms', 'groundhogg' ); ?></h2>
        <div id="table_form_activity"></div>
    </div>
</div>
<div class="groundhogg-chart-wrapper">
    <div class="groundhogg-chart-no-padding full-width">
        <h2 class="title"><?php _e( 'Activity', 'groundhogg' ); ?></h2>
        <div id="table_funnel_stats"></div>
    </div>
</div>