<?php

namespace Groundhogg\Admin\Reports\Views;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

?>
<div class="display-grid gap-20">
    <div class="gh-panel span-12">
        <div class="gh-panel-header">
            <h2 class="title"><?php esc_html_e( 'Email Activity', 'groundhogg' ); ?></h2>
        </div>
        <div class="big-chart-wrap">
            <canvas id="chart_email_activity"></canvas>
        </div>
    </div>

	<?php quick_stat_report( [
		'id'    => 'total_emails_sent',
		'title' => esc_html__( 'Emails Sent', 'groundhogg' ),
		'class' => 'span-4'
	] );

	quick_stat_report( [
		'id'    => 'email_open_rate',
		'title' => esc_html__( 'Open Rate', 'groundhogg' ),
		'class' => 'span-4'
	] );

	quick_stat_report( [
		'id'    => 'email_click_rate',
		'title' => esc_html__( 'Click Thru Rate', 'groundhogg' ),
		'class' => 'span-4'
	] );
	quick_stat_report( [
		'id'    => 'total_unsubscribed_contacts',
		'title' => esc_html__( 'Unsubscribes', 'groundhogg' ),
	] );
	quick_stat_report( [
		'id'    => 'total_spam_contacts',
		'title' => esc_html__( 'Spam', 'groundhogg' ),
	] );
	quick_stat_report( [
		'id'    => 'total_bounces_contacts',
		'title' => esc_html__( 'Bounces', 'groundhogg' ),
	] );
	quick_stat_report( [
		'id'    => 'total_complaints_contacts',
		'title' => esc_html__( 'Complaints', 'groundhogg' ),
	] ); ?>

	<?php do_action( 'groundhogg/admin/reports/pages/email/after_quick_stats' ); ?>

    <div class="gh-panel span-6">
        <div class="gh-panel-header">
            <h2 class="title"><?php esc_html_e( 'Last Broadcast', 'groundhogg' ); ?></h2>
        </div>
        <div class="inside">
            <canvas id="chart_last_broadcast"></canvas>
        </div>
        <div id="table_broadcast_stats" style="margin-top: 10px"></div>
    </div>

    <div class="gh-panel span-6">
        <div class="gh-panel-header">
            <h2 class="title"><?php esc_html_e( 'Top Performing Broadcasts', 'groundhogg' ); ?></h2>
        </div>
        <div id="table_top_performing_broadcasts" class="emails-list"></div>
    </div>

    <div class="gh-panel span-6">
        <div class="gh-panel-header">
            <h2 class="title"><?php esc_html_e( 'Top Performing Flow Emails', 'groundhogg' ); ?></h2>
        </div>
        <div id="table_top_performing_emails" class="emails-list"></div>
    </div>
    <div class="gh-panel span-6">
        <div class="gh-panel-header">
            <h2 class="title"><?php esc_html_e( 'Flow Emails Needing Improvement', 'groundhogg' ); ?></h2>
        </div>
        <div id="table_worst_performing_emails" class="emails-list"></div>
    </div>
</div>
