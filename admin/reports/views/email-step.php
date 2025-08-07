<?php

namespace Groundhogg\Admin\Reports\Views;

use Groundhogg\Step;
use function Groundhogg\get_url_var;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

$step = new Step( get_url_var( 'step' ) );

?>
<div class="display-flex gap-20 align-center">
    <h1 class="report-title"><?php _e( $step->get_title() ) ?></h1>
</div>
<div class="display-grid gap-20">
    <div class="gh-panel span-6">
        <div class="gh-panel-header">
            <h2 class="title"><?php _e( 'Email Stats', 'groundhogg' ); ?></h2>
        </div>
        <div class="inside">
            <canvas id="chart_donut_email_stats"></canvas>
        </div>
    </div>
    <div class="gh-panel span-6">
        <div class="gh-panel-header">
            <h2 class="title"><?php _e( 'Email Stats', 'groundhogg' ); ?></h2>
        </div>
        <div id="table_email_stats"></div>
    </div>

	<?php quick_stat_report( [
		'id'    => 'total_emails_sent',
		'title' => __( 'Sent', 'groundhogg' ),
		'class' => 'span-4'
	] ); ?>

	<?php quick_stat_report( [
		'id'    => 'email_open_rate',
		'title' => __( 'Open Rate', 'groundhogg' ),
		'class' => 'span-4'
	] ); ?>

	<?php quick_stat_report( [
		'id'    => 'email_click_rate',
		'title' => __( 'Click Thru Rate', 'groundhogg' ),
		'class' => 'span-4'
	] ); ?>

	<?php do_action( 'groundhogg/admin/reports/pages/email_step/after_quick_stats' ); ?>

    <div class="gh-panel span-12">
        <div class="gh-panel-header">
            <h2 class="title"><?php _e( 'Links Clicked', 'groundhogg' ); ?></h2>
        </div>
        <div id="table_email_links_clicked"></div>
    </div>
</div>
