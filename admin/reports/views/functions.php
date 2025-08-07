<?php

namespace Groundhogg\Admin\Reports\Views;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

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
		'style' => [],
		'info'  => '',
	] );

	?>

    <div class="groundhogg-quick-stat gh-panel <?php esc_attr_e( $args['class'] ); ?>" id="<?php esc_attr_e( $args['id'] ); ?>">
        <div class="gh-panel-header">
            <h2 class="title">
				<?php esc_html_e( $args['title'] ) ?>
				<?php if ( $args['info'] ): ?>
                    <span class="gh-has-tooltip dashicons dashicons-info">
                        <span class="gh-tooltip top">
                            <?php _e( $args['info'] ); ?>
                        </span>
                    </span>
				<?php endif; ?>
            </h2>
        </div>
        <div class="inside">
            <div class="display-flex align-center flex-wrap">
                <div class="groundhogg-quick-stat-number">...</div>
                <div style="margin-left: auto">
                    <div class="groundhogg-quick-stat-previous green">
                        <span class="groundhogg-quick-stat-arrow up"></span>
                        <span class="groundhogg-quick-stat-prev-percent">0%</span>
                    </div>
                    <div class="groundhogg-quick-stat-compare">vs. Previous 30 Days</div>
                </div>
            </div>
            <div class="wp-clearfix"></div>
        </div>
    </div>
	<?php
}
