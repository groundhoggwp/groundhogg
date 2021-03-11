<?php

namespace Groundhogg\Admin\Reports\Views;

?>
<div class="groundhogg-report">
	<h2 class="title"><?php _e( 'Email Activity', 'groundhogg' ); ?></h2>
	<div class="big-chart-wrap">
		<canvas id="chart_email_activity"></canvas>
	</div>
</div>

<div class="groundhogg-quick-stats">
	<div class="groundhogg-report">

		<?php quick_stat_report( [
			'id'    => 'total_emails_sent',
			'title' => __( 'Emails Sent', 'groundhogg' ),
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
<div class="groundhogg-quick-stats">
	<div class="groundhogg-report">
		<?php quick_stat_report( [
			'id'    => 'total_unsubscribed_contacts',
			'title' => __( 'Unsubscribes', 'groundhogg' ),
			'style' => 'width:25%;'
		] );
		?>
		<?php quick_stat_report( [
			'id'    => 'total_spam_contacts',
			'title' => __( 'Spam', 'groundhogg' ),
			'style' => 'width:25%;'
		] ); ?>

		<?php quick_stat_report( [
			'id'    => 'total_bounces_contacts',
			'title' => __( 'Bounces', 'groundhogg' ),
			'style' => 'width:25%;'
		] ); ?>

		<?php quick_stat_report( [
			'id'    => 'total_complaints_contacts',
			'title' => __( 'Complaints', 'groundhogg' ),
			'style' => 'width:25%;'
		] ); ?>

	</div>
</div>
<?php do_action( 'groundhogg/admin/reports/pages/email/after_quick_stats' ); ?>
<div class="groundhogg-chart-wrapper">
	<div class="groundhogg-chart-no-padding">
		<h2 class="title"><?php _e( 'Last Broadcast', 'groundhogg' ); ?></h2>
		<div style="width: 100%;">
			<div class="float-left" style="width:60%">
				<canvas id="chart_last_broadcast"></canvas>
			</div>
			<div class="float-left" style="width:40%">
				<div id="chart_last_broadcast_legend" class="chart-legend"></div>
			</div>
		</div>
		<div class="wp-clearfix"></div>
		<div id="table_broadcast_stats" style="margin-top: 10px"></div>
	</div>
	<div class="groundhogg-chart-no-padding">
		<h2 class="title"><?php _e( 'Top Performing Broadcasts', 'groundhogg' ); ?></h2>
		<div id="table_top_performing_broadcasts" class="emails-list"></div>
	</div>
</div>

<div class="groundhogg-chart-wrapper">
	<div class="groundhogg-chart-no-padding">
		<h2 class="title"><?php _e( 'Top Performing Funnel Emails', 'groundhogg' ); ?></h2>
		<div id="table_top_performing_emails" class="emails-list"></div>
	</div>
	<div class="groundhogg-chart-no-padding">
		<h2 class="title"><?php _e( 'Funnel Emails Needing Improvement', 'groundhogg' ); ?></h2>
		<div id="table_worst_performing_emails" class="emails-list"></div>
	</div>
</div>