<?php

namespace Groundhogg\Admin\Reports\Views;

// Overview

use function Groundhogg\html;
use function Groundhogg\is_white_labeled;

?>
<div class="groundhogg-report">
    <h2 class="title"><?php _e( 'New Contacts', 'groundhogg' ); ?></h2>
    <div class="big-chart-wrap">
        <canvas id="chart_new_contacts"></canvas>
    </div>
</div>
<div class="groundhogg-quick-stats">
    <div class="groundhogg-report">

		<?php quick_stat_report( [
			'id'    => 'total_new_contacts',
			'title' => __( 'New Contacts', 'groundhogg' )
		] ); ?>

		<?php quick_stat_report( [
			'id'    => 'total_confirmed_contacts',
			'title' => __( 'Confirmed Contacts', 'groundhogg' ),
		] ); ?>

		<?php quick_stat_report( [
			'id'    => 'total_engaged_contacts',
			'title' => __( 'Engaged Contacts', 'groundhogg' ),
		] ); ?>
`
		<?php quick_stat_report( [
			'id'    => 'total_unsubscribed_contacts',
			'title' => __( 'Unsubscribed Contacts', 'groundhogg' ),
		] ); ?>
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

<div class="groundhogg-chart-wrapper">
    <div class="groundhogg-chart">
        <h2 class="title"><?php _e( 'Opt-in Status', 'groundhogg' ); ?></h2>
        <div style="width: 100%">
            <div class="float-left" style="width:60%; display:inline-block">
                <canvas id="chart_contacts_by_optin_status"></canvas>
            </div>
            <div class="float-left" style="width:40%;display: inline-block">
                <div id="chart_contacts_by_optin_status_legend" class="chart-legend"></div>
            </div>

        </div>
    </div>
    <div class="groundhogg-chart">
        <h2 class="title"><?php _e( 'Lead Score', 'groundhogg' ); ?></h2>
		<?php if ( has_action( 'groundhogg/admin/report/lead_score' ) ) : ?>
			<?php do_action( 'groundhogg/admin/report/lead_score' ); ?>
		<?php else : ?>
            <p class="notice-no-data">
				<?php _e( 'Please Enable Lead Scoring Plugin to view this data.', 'groundhogg' );
				if ( ! is_white_labeled() ) {
					echo html()->wrap( 'Click Here To Download', 'a', [ 'href' => 'https://www.groundhogg.io/downloads/lead-scoring/' ] );
				}
				?>
            </p>
		<?php endif; ?>
    </div>
</div>

<div class="groundhogg-chart-wrapper">
    <div class="groundhogg-chart-no-padding">
        <h2 class="title"><?php _e( 'Top Converting Funnels', 'groundhogg' ); ?></h2>
        <div id="table_top_converting_funnels" class="inner"></div>
    </div>
    <div class="groundhogg-chart-no-padding">
        <h2 class="title"><?php _e( 'Top Performing Funnel Emails', 'groundhogg' ); ?></h2>
        <div id="table_top_performing_emails" class="inner emails-list"></div>
    </div>
</div>

<div class="groundhogg-chart-wrapper">
    <div class="groundhogg-chart-no-padding">
        <h2 class="title"><?php _e( 'Top Countries', 'groundhogg' ); ?></h2>
        <div id="table_contacts_by_countries" class="inner"></div>
    </div>
    <div class="groundhogg-chart-no-padding">
        <h2 class="title"><?php _e( 'Top lead Sources', 'groundhogg' ); ?></h2>
        <div id="table_contacts_by_lead_source" class="inner"></div>
    </div>
</div>