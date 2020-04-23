<?php

namespace Groundhogg\Admin\Reports\Views;

// Overview

use Groundhogg\Classes\Activity;
use function Groundhogg\get_db;


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


<div class="groundhogg-report">
    <h2 class="title"><?php _e( 'Email Activity', 'groundhogg' ); ?></h2>
    <div style="height: 400px">
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
			'title' => __( 'Click Rate', 'groundhogg' ),
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
<div class="groundhogg-chart-wrapper">
    <div class="groundhogg-chart-no-padding">
        <h2 class="title"><?php _e( 'Last Broadcast', 'groundhogg' ); ?></h2>
        <div style="width: 100%; padding: ">
            <div class="float-left" style="width:60%">
                <canvas id="chart_last_broadcast"></canvas>
            </div>
            <div class="float-left" style="width:40%">
                <div id="chart_last_broadcast_legend" class="chart-legend"></div>
            </div>
        </div>
    </div>
    <div class="groundhogg-chart-no-padding">
        <h2 class="title"><?php _e( 'Top Performing Broadcasts', 'groundhogg' ); ?></h2>
        <div id="table_top_performing_broadcasts"></div>
    </div>
</div>

<div class="groundhogg-chart-wrapper">
    <div class="groundhogg-chart-no-padding">
        <h2 class="title"><?php _e( 'Top Performing Funnel Emails', 'groundhogg' ); ?></h2>
        <div id="table_top_performing_emails"></div>
    </div>
    <div class="groundhogg-chart-no-padding">
        <h2 class="title"><?php _e( 'Funnel Emails Needing Improvement', 'groundhogg' ); ?></h2>
        <div id="table_worst_performing_emails"></div>
    </div>
</div>
<!--<div class="groundhogg-chart-wrapper">-->
<!--    <div class="groundhogg-chart-no-padding" style="width: 100% ; margin-right: 0px;">-->
<!--        <h2 class="title">Funnel Emails Needing Improvement</h2>-->
<!--        <div id="table_worst_performing_emails"></div>-->
<!--    </div>-->
<!--</div>-->