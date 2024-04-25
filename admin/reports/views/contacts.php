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
		'title' => __( 'New Contacts', 'groundhogg' )
	] ); ?>

	<?php quick_stat_report( [
		'id'    => 'total_confirmed_contacts',
		'title' => __( 'Confirmed Contacts', 'groundhogg' ),
	] ); ?>

	<?php quick_stat_report( [
		'id'    => 'total_engaged_contacts',
		'title' => __( 'Engaged Contacts', 'groundhogg' ),
		'info' => __( 'An engaged contact is anyone who has any activity within the time range.', 'groundhogg' )
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

    <div class="gh-panel span-6">
        <div class="gh-panel-header">
            <h2 class="title">
				<?php _e( 'Unsubscribe Reasons', 'groundhogg' ); ?>
                <span class="gh-has-tooltip dashicons dashicons-info">
                    <span class="gh-tooltip top">
                        <?php _e( 'This chart shows individual unsubscribe events with their reasons, which may be larger that the total number of unsubscribed contacts.' ) ?>
                    </span>
                </span>
            </h2>
        </div>
        <div class="gh-donut-chart-wrap inside">
            <canvas id="chart_unsub_reasons"></canvas>
        </div>
    </div>

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
            <h2 class="title"><?php _e( 'Engagement', 'groundhogg' ); ?></h2>
        </div>
        <div id="table_list_engagement"></div>
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
            <h2 class="title"><?php _e( 'Top Signup Pages', 'groundhogg' ); ?></h2>
        </div>
        <div id="table_contacts_by_source_page"></div>
    </div>
    <div class="gh-panel span-6">
        <div class="gh-panel-header">
            <h2 class="title"><?php _e( 'Top lead Sources', 'groundhogg' ); ?></h2>
        </div>
        <div id="table_contacts_by_lead_source"></div>
    </div>

</div>
