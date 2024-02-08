<?php

namespace Groundhogg\Admin\Reports\Views;

// Overview
use Groundhogg\Funnel;
use function Groundhogg\get_url_var;
use function Groundhogg\html;

$funnel = new Funnel( get_url_var( 'funnel' ) );

if ( ! $funnel->exists() ) {
	wp_die( 'Funnel does not exist!' );
}

?>

<div class="display-flex gap-20 align-center">
    <h1 class="report-title"><?php _e( $funnel->get_title() ) ?></h1>
	<?php echo html()->e( 'a', [
		'target' => '_blank',
		'id'     => 'edit-funnel',
		'href'   => $funnel->admin_link(),
		'class'  => 'gh-button secondary'
	], __( 'Edit Funnel' ) ) ?>
</div>
<div class="display-grid gap-20">

	<?php quick_stat_report( [
		'id'    => 'total_contacts_added_to_funnel',
		'title' => __( 'Contacts Added', 'groundhogg' ),
		'class' => 'span-3',
	] ); ?>

	<?php quick_stat_report( [
		'id'    => 'total_contacts_in_funnel',
		'title' => __( 'Active Contacts', 'groundhogg' ),
		'class' => 'span-3',
	] ); ?>

	<?php quick_stat_report( [
		'id'    => 'total_funnel_conversions',
		'title' => __( 'Conversions', 'groundhogg' ),
		'class' => 'span-3',
	] );
	?>

	<?php quick_stat_report( [
		'id'    => 'total_funnel_conversion_rate',
		'title' => __( 'Conversion Rate', 'groundhogg' ),
		'class' => 'span-3',
	] ); ?>

	<?php do_action( 'groundhogg/admin/reports/pages/funnels/after_quick_stats' ); ?>

    <div class="gh-panel span-12">
        <div class="gh-panel-header">
            <h2 class="title"><?php _e( 'Funnel Breakdown', 'groundhogg' ); ?></h2>
        </div>
        <div class="big-chart-wrap">
            <canvas id="chart_funnel_breakdown"></canvas>
        </div>
    </div>

	<?php

	quick_stat_report( [
		'id'    => 'total_emails_sent',
		'title' => __( 'Emails Sent', 'groundhogg' ),
		'class' => 'span-4'
	] );
	quick_stat_report( [
		'id'    => 'email_open_rate',
		'title' => __( 'Open Rate', 'groundhogg' ),
		'class' => 'span-4'
	] );
	quick_stat_report( [
		'id'    => 'email_click_rate',
		'title' => __( 'Click Thru Rate', 'groundhogg' ),
		'class' => 'span-4'
	] ); ?>

    <div class="gh-panel span-12">
        <div class="gh-panel-header">
            <h2 class="title"><?php _e( 'Email Performance', 'groundhogg' ); ?></h2>
        </div>
        <div id="table_all_funnel_emails_performance"></div>
    </div>

    <div class="gh-panel span-12">
        <div class="gh-panel-header">
            <h2 class="title"><?php _e( 'Forms', 'groundhogg' ); ?></h2>
        </div>
        <div id="table_form_activity"></div>
    </div>

    <div class="gh-panel span-12">
        <div class="gh-panel-header">
            <h2 class="title"><?php _e( 'Activity', 'groundhogg' ); ?></h2>
        </div>
        <div id="table_funnel_stats"></div>
    </div>
</div>

<?php do_action( 'groundhogg/admin/reports/pages/funnels/after_reports' ); ?>
