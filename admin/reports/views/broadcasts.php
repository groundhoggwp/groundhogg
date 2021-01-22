<?php

namespace Groundhogg\Admin\Reports\Views;

use Groundhogg\Broadcast;
use Groundhogg\Funnel;
use Groundhogg\Plugin;
use function Groundhogg\get_db;
use function Groundhogg\get_url_var;

$broadcasts = get_db( 'broadcasts' );
$broadcasts = $broadcasts->query( [ 'status' => 'sent' ] );

$options = [];

foreach ( $broadcasts as $broadcast ) {
	$broadcast                       = new Broadcast( absint( $broadcast->ID ) );
	$options[ $broadcast->get_id() ] = $broadcast->get_title();
}


?>
<div class="actions" style="float: right">
	<?php
	$args = array(
		'name'        => 'broadcast_id',
		'id'          => 'broadcast-id',
		'class'       => 'post-data',
		'selected'    => absint( get_url_var( 'broadcast' ) ),
		'options'     => $options,
		'option_none' => false,
	);
	echo Plugin::$instance->utils->html->dropdown( $args );
	?>

</div>

<div style="clear: both;"></div>

<div class="groundhogg-chart-wrapper">
    <div class="groundhogg-chart">
        <h2 class="title"><?php _e( 'Broadcast Stats', 'groundhogg' ); ?></h2>
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
        <h2 class="title"><?php _e( 'Broadcast Stats', 'groundhogg' ); ?></h2>
        <div id="table_broadcast_stats"></div>
    </div>
</div>

<div class="groundhogg-chart-wrapper">
    <div class="groundhogg-chart-no-padding" style="width: 100% ; margin-right: 0px;">
        <h2 class="title"><?php _e( 'Broadcast Link Clicked', 'groundhogg' ); ?></h2>
        <div id="table_broadcast_link_clicked"></div>
    </div>
</div
