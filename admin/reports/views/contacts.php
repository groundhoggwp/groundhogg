<?php

namespace Groundhogg\Admin\Reports\Views;

// Overview

use Groundhogg\Plugin;
use function Groundhogg\html;
use function Groundhogg\is_white_labeled;

?>
<div class="groundhogg-report">
    <h2 class="title"><?php _e( 'New Contacts', 'groundhogg' ); ?></h2>
    <div class="big-chart-wrap">
        <canvas id="chart_new_contacts"></canvas>
    </div>
</div>

<div style="clear: both;"></div>

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

		<?php quick_stat_report( [
			'id'    => 'total_unsubscribed_contacts',
			'title' => __( 'Unsubscribes', 'groundhogg' ),
		] ); ?>
    </div>
</div>

<div class="groundhogg-chart-wrapper">
    <div class="groundhogg-chart">
        <h2 class="title"><?php _e( 'Opt-in Status', 'groundhogg' ); ?></h2>
        <div class="gh-donut-chart-wrap">
            <div class="float-left" style="width:60%">
                <canvas id="chart_contacts_by_optin_status"></canvas>
            </div>
            <div class="float-left" style="width:40%">
                <div id="chart_contacts_by_optin_status_legend" class="chart-legend"></div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/leadscoring.php'; ?>
</div>

<div class="groundhogg-chart-wrapper">
    <div class="groundhogg-chart">
        <h2 class="title"><?php _e( 'Contacts By Country', 'groundhogg' ); ?></h2>
        <div class="gh-donut-chart-wrap">
            <div class="float-left" style="width:60%">
                <canvas id="chart_contacts_by_country"></canvas>
            </div>
            <div class="float-left" style="width:40%">
                <div id="chart_contacts_by_country_legend" class="chart-legend"></div>
            </div>
        </div>
    </div>
    <div class="groundhogg-chart">

        <div style="width: 100%">
            <div class="actions" style="float:left;width: 50%;">
                <h2 class="title"><?php _e( 'Contacts By Region', 'groundhogg' ); ?></h2>
            </div>
            <div class="actions" style="float: right ; width: 50%;  margin-block-start: 0.83em;margin-block-end: 0.83em;">
				<?php
				$args = array(
					'name'        => 'country',
					'id'          => 'country',
					'class'       => 'post-data',
					'data'        => Plugin::$instance->utils->location->get_countries_list( '', true ),
					'selected'    => [ Plugin::$instance->utils->location->site_country_code() ],
					'option_none' => false,
					'style'       => false
				);

				if ( ! empty( $args[ 'data' ] ) ){
					echo Plugin::$instance->utils->html->select2( $args );
				}

				?>
            </div>
            <div class="wp-clearfix"></div>
        </div>
        <div class="gh-donut-chart-wrap">
            <div class="float-left" style="width:60%">
                <canvas id="chart_contacts_by_region"></canvas>
            </div>
            <div class="float-left" style="width:40%">
                <div id="chart_contacts_by_region_legend" class="chart-legend"></div>
            </div>
        </div>
    </div>
</div>

<div class="groundhogg-chart-wrapper">
    <div class="groundhogg-chart-no-padding">
        <h2 class="title"><?php _e( 'Top Search Engines', 'groundhogg' ); ?></h2>
        <div id="table_contacts_by_search_engines"></div>
    </div>
    <div class="groundhogg-chart-no-padding">
        <h2 class="title"><?php _e( 'Top Social Networks', 'groundhogg' ); ?></h2>
        <div id="table_contacts_by_social_media"></div>
    </div>
</div>
<div class="groundhogg-chart-wrapper">
    <div class="groundhogg-chart-no-padding">
        <h2 class="title"><?php _e( 'Top Source Pages', 'groundhogg' ); ?></h2>
        <div id="table_contacts_by_source_page"></div>
    </div>
    <div class="groundhogg-chart-no-padding">
        <h2 class="title"><?php _e( 'Top lead Sources', 'groundhogg' ); ?></h2>
        <div id="table_contacts_by_lead_source"></div>
    </div>
</div>


