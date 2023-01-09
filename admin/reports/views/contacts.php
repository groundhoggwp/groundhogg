<?php

namespace Groundhogg\Admin\Reports\Views;

// Overview

use function Groundhogg\html;
use function Groundhogg\utils;

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

    <div class="gh-panel span-6">
        <div class="gh-panel-header">
            <h2 class="title"><?php _e( 'Opt-in Status', 'groundhogg' ); ?></h2>
        </div>
        <div class="gh-donut-chart-wrap inside">
            <canvas id="chart_contacts_by_optin_status"></canvas>
        </div>
    </div>

	<?php include __DIR__ . '/leadscoring.php'; ?>

    <div class="gh-panel span-6">
        <div class="gh-panel-header">
            <h2 class="title"><?php _e( 'Contacts By Country', 'groundhogg' ); ?></h2>
        </div>
        <div class="gh-donut-chart-wrap inside">
            <canvas id="chart_contacts_by_country"></canvas>
        </div>
    </div>

    <div class="gh-panel span-6">
        <div class="gh-panel-header">
            <h2 class="title"><?php _e( 'Contacts By Region', 'groundhogg' ); ?></h2>
            <div class="actions">
				<?php

				echo html()->select2( [
					'name'        => 'country',
					'id'          => 'country',
					'class'       => 'gh-select2 post-data',
					'data'        => utils()->location->get_countries_list( '', true ),
					'selected'    => [ utils()->location->site_country_code() ],
					'option_none' => false,
				] );

				?>
            </div>
        </div>
        <div class="gh-donut-chart-wrap inside">
            <canvas id="chart_contacts_by_region"></canvas>
        </div>
    </div>

    <div class="gh-panel span-6">
        <div class="gh-panel-header">
            <h2 class="title"><?php _e( 'Top Search Engines', 'groundhogg' ); ?></h2>
        </div>
        <div id="table_contacts_by_search_engines"></div>
    </div>
    <div class="gh-panel span-6">
        <div class="gh-panel-header">
            <h2 class="title"><?php _e( 'Top Social Networks', 'groundhogg' ); ?></h2>
        </div>
        <div id="table_contacts_by_social_media"></div>
    </div>

    <div class="gh-panel span-6">
        <div class="gh-panel-header">
            <h2 class="title"><?php _e( 'Top Source Pages', 'groundhogg' ); ?></h2>
        </div>
        <div id="table_contacts_by_source_page"></div>
    </div>
    <div class="gh-panel span-6">
        <div class="gh-panel-header">
            <h2 class="title"><?php _e( 'Top lead Sources', 'groundhogg' ); ?></h2>
        </div>
        <div id="table_contacts_by_lead_source"></div>
    </div>

    <div class="gh-panel span-12">
        <div class="gh-panel-header">
            <h2 class="title"><?php _e( 'Engagement', 'groundhogg' ); ?></h2>
        </div>
        <div id="table_list_engagement"></div>
    </div>
</div>
