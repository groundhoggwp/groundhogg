<?php

namespace Groundhogg\Admin\Reports\Views;

// Overview
?>
<div class="display-grid gap-20">
    <div class="gh-panel span-12">
        <div class="gh-panel-header">
            <h2 class="title"><?php _e( 'New Contacts', 'groundhogg' ); ?></h2>
        </div>
        <div class="big-chart-wrap">
            <canvas id="chart_new_contacts"></canvas>
        </div>
    </div>
	<?php quick_stat_report( [
		'id'    => 'total_new_contacts',
		'title' => __( 'New Contacts', 'groundhogg' ),
		'class' => 'span-3'
	] );
	quick_stat_report( [
		'id'    => 'total_confirmed_contacts',
		'title' => __( 'Confirmed Contacts', 'groundhogg' ),
		'class' => 'span-3'
	] );
	quick_stat_report( [
		'id'    => 'total_engaged_contacts',
		'title' => __( 'Engaged Contacts', 'groundhogg' ),
		'class' => 'span-3',
		'info' => __( 'An engaged contact is anyone who has any activity within the time range.', 'groundhogg' )
	] );
	quick_stat_report( [
		'id'    => 'total_unsubscribed_contacts',
		'title' => __( 'Unsubscribed Contacts', 'groundhogg' ),
		'class' => 'span-3'
	] );
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

    <div class="gh-panel span-6">
        <div class="gh-panel-header">
            <h2 class="title"><?php _e( 'Top Converting Flows', 'groundhogg' ); ?></h2>
        </div>
        <div id="table_top_converting_funnels" class="inner"></div>
    </div>

    <div class="gh-panel span-6">
        <div class="gh-panel-header">
            <h2 class="title"><?php _e( 'Top Performing Flow Emails', 'groundhogg' ); ?></h2>
        </div>
        <div id="table_top_performing_emails" class="inner emails-list"></div>
    </div>

    <div class="gh-panel span-6">
        <div class="gh-panel-header">
            <h2 class="title"><?php _e( 'Top Countries', 'groundhogg' ); ?></h2>
        </div>
        <div id="table_contacts_by_countries" class="inner"></div>
    </div>
    <div class="gh-panel span-6">
        <div class="gh-panel-header">
            <h2 class="title"><?php _e( 'Top lead Sources', 'groundhogg' ); ?></h2>
        </div>
        <div id="table_contacts_by_lead_source" class="inner"></div>
    </div>
</div>
