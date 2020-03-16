<?php

namespace Groundhogg\Admin\Reports\Views;

// Overview

function get_img_url( $img ) {
	echo esc_url( GROUNDHOGG_ASSETS_URL . 'images/reports/' . $img );
}

function quick_stat_report( $args = [] ) {
	$args = wp_parse_args( $args, [
		'id'    => uniqid( 'groundhogg_' ),
		'title' => 'Report',
		'info'  => 'Some interesting data...' ,
		'style' => ''
	] );

	?>

    <div class="groundhogg-quick-stat" id="<?php esc_attr_e( $args[ 'id' ] ); ?>" style="<?php esc_attr_e( $args[ 'style' ] ); ?>">
        <div class="groundhogg-quick-stat-title"><?php esc_html_e( $args['title'] ) ?></div>
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
    <h2 class="title"><?php _e( 'New Contacts', 'groundhogg' ); ?></h2>
    <canvas id="chart_new_contacts"></canvas>
</div>

<div class="groundhogg-quick-stats">
    <div class="groundhogg-report">

		<?php quick_stat_report( [
		        'id' => 'total_new_contacts',
                'title' => __( 'New Contacts', 'groundhogg' )
        ] ); ?>

		<?php quick_stat_report( [
		        'id' => 'total_confirmed_contacts',
		        'title' => __( 'Confirmed Contacts', 'groundhogg' ),
        ] ); ?>

		<?php quick_stat_report( [
		        'id' => 'total_engaged_contacts',
		        'title' => __( 'Engaged Contacts', 'groundhogg' ),
        ] ); ?>

		<?php quick_stat_report( [
		        'id' => 'total_unsubscribes',
		        'title' => __( 'Unsubscribes', 'groundhogg' ),
		] ); ?>
    </div>
</div>

<div class="groundhogg-quick-stats">
    <div class="groundhogg-report">

		<?php quick_stat_report( [
			'id' => 'total_emails_sent',
			'title' => __( 'Emails Sent', 'groundhogg' ),
			'style' =>'width:33%;'
		] ); ?>

		<?php quick_stat_report( [
			'id' => 'email_open_rate',
			'title' => __( 'Open Rate', 'groundhogg' ),
			'style' =>'width:33%;'
		] ); ?>

		<?php quick_stat_report( [
			'id' => 'email_click_rate',
			'title' => __( 'Click Rate', 'groundhogg' ),
			'style' =>'width:33%;'
		] ); ?>

    </div>
</div>

<div class="groundhogg-chart-wrapper">
    <div class="groundhogg-chart">
        <h2 class="title"><?php _e( 'Opt-in Status', 'groundhogg' ); ?></h2>
        <canvas id="chart_contacts_by_optin_status"></canvas>
    </div>
    <div class="groundhogg-chart">
        <h2 class="title"><?php _e( 'Lead Score', 'groundhogg' ); ?></h2>
        <p class="title"><?php _e( 'TO BE DECIDED WHAT TO DISPLAY ', 'groundhogg' ); ?></p>
    </div>
</div>


<div class="groundhogg-chart-wrapper">
    <div class="groundhogg-chart-no-padding">
        <h2 class="title"><?php _e( 'Top Converting Funnels', 'groundhogg' ); ?></h2>
        <p> TO BE DECIDED HOW TO DO IT</p>
    </div>
    <div class="groundhogg-chart-no-padding">
        <h2 class="title"><?php _e( 'Top Performing Funnel Emails', 'groundhogg' ); ?></h2>
        <div id="table_top_performing_emails"></div>
    </div>
</div>



<div class="groundhogg-chart-wrapper">
    <div class="groundhogg-chart-no-padding">
        <h2 class="title"><?php _e( 'Top Countries', 'groundhogg' ); ?></h2>
        <div id="table_contacts_by_countries"></div>
    </div>
    <div class="groundhogg-chart-no-padding">
        <h2 class="title"><?php _e( 'Top lead Sources', 'groundhogg' ); ?></h2>
        <div id="table_contacts_by_lead_source"></div>
    </div>

</div>