<?php

namespace Groundhogg\Admin\Reports\Views;

use function Groundhogg\array_to_css;

function get_img_url( $img ) {
	echo esc_url( GROUNDHOGG_ASSETS_URL . 'images/reports/' . $img );
}

/**
 * Table chart
 *
 * @param array $args
 */
function table_chart_report( $args = [] ) {
	$args = wp_parse_args( $args, [
		'id'    => uniqid( 'groundhogg_' ),
		'title' => 'Report',
		'info'  => 'Some interesting data...',
		'class' => false,
	] );

	?>
	<div class="groundhogg-chart-no-padding <?php esc_attr_e( $args['class'] ); ?>">
		<h2 class="title"><?php esc_html_e( $args['title'] ); ?></h2>
		<div id="<?php esc_attr_e( $args['id'] ); ?>"></div>
	</div>
	<?php
}

/**
 * Donut chart
 *
 * @param array $args
 */
function donut_chart_report( $args = [] ) {
	$args = wp_parse_args( $args, [
		'id'    => uniqid( 'groundhogg_' ),
		'title' => 'Report',
		'info'  => 'Some interesting data...',
	] );

	?>
	<div class="groundhogg-chart">
		<h2 class="title"><?php esc_html_e( $args['title'] ); ?></h2>
		<div style="width: 100%; padding: ">
			<div class="float-left donut-chart-left">
				<canvas id="<?php esc_attr_e( $args['id'] ); ?>"></canvas>
			</div>
			<div class="float-left donut-chart-right">
				<div id="<?php esc_attr_e( $args['id'] ); ?>_legend" class="chart-legend"></div>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Output html for a quick stat report
 *
 * @param array $args
 */
function quick_stat_report( $args = [] ) {

	$args = wp_parse_args( $args, [
		'id'    => uniqid( 'groundhogg_' ),
		'title' => 'Report',
        'class' => 'span-3',
		'info'  => 'Some interesting data...',
		'style' => [],
	] );

	?>

	<div class="groundhogg-quick-stat gh-panel <?php esc_attr_e( $args[ 'class' ] ); ?>" id="<?php esc_attr_e( $args['id'] ); ?>">
        <div class="gh-panel-header">
            <h2 class="groundhogg-quick-stat-title"><?php esc_html_e( $args['title'] ) ?></h2>
        </div>
        <div class="inside">
            <div class="groundhogg-quick-stat-info"></div>
            <div class="groundhogg-quick-stat-number">1234</div>
            <div class="groundhogg-quick-stat-previous green">
                <span class="groundhogg-quick-stat-arrow up"></span>
                <span class="groundhogg-quick-stat-prev-percent">25%</span>
            </div>
            <div class="groundhogg-quick-stat-compare">vs. Previous 30 Days</div>
            <div class="clearfix"></div>
        </div>
	</div>
	<?php
}
