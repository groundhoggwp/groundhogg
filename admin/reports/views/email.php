<?php

namespace Groundhogg\Admin\Reports\Views;

// Overview

use Groundhogg\Classes\Activity;
use function Groundhogg\get_db;


function quick_stat_report( $args = [] ) {

	$args = wp_parse_args( $args, [
		'id'    => uniqid( 'groundhogg_' ),
		'title' => 'Report',
		'info'  => 'Some interesting data...'
	] );

	?>

    <div class="groundhogg-quick-stat" id="<?php esc_attr_e( $args[ 'id' ] ); ?>">
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
    <canvas id="chart_email_activity"></canvas>
</div>


<div class="groundhogg-quick-stats">
    <div class="groundhogg-report">

		<?php quick_stat_report( [
			'id'    => 'total_emails_sent',
			'title' => __( 'Emails Sent', 'groundhogg' )
		] ); ?>

		<?php quick_stat_report( [
			'id'    => 'email_open_rate',
			'title' => __( 'Open Rate', 'groundhogg' ),
		] ); ?>

		<?php quick_stat_report( [
			'id'    => 'email_click_rate',
			'title' => __( 'Click Rate', 'groundhogg' ),
		] ); ?>

        <!--        		--><?php //quick_stat_report( [
		//					'id' => 'unsubscribes',
		//					'title' => __( 'Unsubscribes', 'groundhogg' ),
		//				] );
		?>
    </div>
</div>


<div class="groundhogg-chart-wrapper">
    <div class="groundhogg-chart">
        <h2 class="title"><?php _e( 'Opt-in Status', 'groundhogg' ); ?></h2>
        <canvas id="chart_last_broadcast"></canvas>
    </div>
    <div class="groundhogg-chart">
        <h2 class="title"><?php _e( 'To be decided', 'groundhogg' ); ?></h2>
        <p class="title"><?php _e( 'Something here......', 'groundhogg' ); ?></p>
    </div>
</div>


<div class="groundhogg-chart-wrapper">
    <div class="groundhogg-chart-no-padding">
        <h2 class="title"><?php _e( 'Top Performing Funnel Emails', 'groundhogg' ); ?></h2>
        <div id="table_top_performing_emails"></div>
    </div>
    <div class="groundhogg-chart-no-padding">
        <h2 class="title"><?php _e( 'Top Performing Broadcasts', 'groundhogg' ); ?></h2>
        <div id="table_top_performing_broadcasts"></div>
    </div>
</div>




