<?php

namespace Groundhogg\Admin\Reports\Views;

?>
<div class="display-grid gap-20">
    <div class="gh-panel span-12">
        <div class="gh-panel-header">
            <h2 class="title"><?php _e( 'Email Activity', 'groundhogg' ); ?></h2>
        </div>
        <div class="big-chart-wrap">
            <canvas id="chart_email_activity"></canvas>
        </div>
    </div>

	<?php quick_stat_report( [
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
	] );
	quick_stat_report( [
		'id'    => 'total_unsubscribed_contacts',
		'title' => __( 'Unsubscribes', 'groundhogg' ),
	] );
	quick_stat_report( [
		'id'    => 'total_spam_contacts',
		'title' => __( 'Spam', 'groundhogg' ),
	] );
	quick_stat_report( [
		'id'    => 'total_bounces_contacts',
		'title' => __( 'Bounces', 'groundhogg' ),
	] );
	quick_stat_report( [
		'id'    => 'total_complaints_contacts',
		'title' => __( 'Complaints', 'groundhogg' ),
	] ); ?>

	<?php do_action( 'groundhogg/admin/reports/pages/email/after_quick_stats' ); ?>

    <div class="gh-panel span-6">
        <div class="gh-panel-header">
            <h2 class="title"><?php _e( 'Last Broadcast', 'groundhogg' ); ?></h2>
        </div>
        <div class="inside">
            <canvas id="chart_last_broadcast"></canvas>
        </div>
        <div id="table_broadcast_stats" style="margin-top: 10px"></div>
    </div>

    <div class="gh-panel span-6">
        <div class="gh-panel-header">
            <h2 class="title"><?php _e( 'Top Performing Broadcasts', 'groundhogg' ); ?></h2>
        </div>
        <div id="table_top_performing_broadcasts" class="emails-list"></div>
    </div>

    <div class="gh-panel span-6">
        <div class="gh-panel-header">
            <h2 class="title"><?php _e( 'Top Performing Flow Emails', 'groundhogg' ); ?></h2>
        </div>
        <div id="table_top_performing_emails" class="emails-list"></div>
    </div>
    <div class="gh-panel span-6">
        <div class="gh-panel-header">
            <h2 class="title"><?php _e( 'Flow Emails Needing Improvement', 'groundhogg' ); ?></h2>
        </div>
        <div id="table_worst_performing_emails" class="emails-list"></div>
    </div>
</div>
