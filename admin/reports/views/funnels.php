<?php

namespace Groundhogg\Admin\Reports\Views;

// Overview

use Groundhogg\Classes\Activity;
use Groundhogg\Funnel;
use Groundhogg\Plugin;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use function Groundhogg\html;

function get_img_url( $img ) {
	echo esc_url( GROUNDHOGG_ASSETS_URL . 'images/reports/' . $img );
}

function get_funnel_id() {
     if(get_request_var( 'funnel' )) {
         return absint(get_request_var( 'funnel' ));
     }

	return Plugin::$instance->reporting->get_report( 'complete_funnel_activity' )->get_funnel_id();
}

$funnels = get_db( 'funnels' );
$funnels = $funnels->query( [ 'status' => 'active' ] );

$options = [];

foreach ( $funnels as $funnel ) {
	$funnel                       = new Funnel( absint( $funnel->ID ) );
	$options[ $funnel->get_id() ] = $funnel->get_title();
}

?>
<div class="actions">
<!--    <form method="get" action="">-->
		<?php
//		echo html()->hidden_GET_inputs();

		$args = array(
			'name'        => 'funnel-id',
			'id'          => 'funnel-id',
			'options'     => $options,
			'selected'    => [get_funnel_id()],
			'option_none' => false,
		);
		echo Plugin::$instance->utils->html->dropdown( $args );
		?>
<!--    </form>-->
</div>




<div class="groundhogg-report">
    <h2 class="title"><?php _e( 'Email Activity', 'groundhogg' ); ?></h2>
    <canvas id="chart_funnel_breakdown"></canvas>
</div>
