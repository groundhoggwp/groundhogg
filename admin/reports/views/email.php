<?php

namespace Groundhogg\Admin\Reports\Views;

// Overview

use Groundhogg\Classes\Activity;
use function Groundhogg\get_db;

function get_img_url( $img ) {
	echo esc_url( GROUNDHOGG_ASSETS_URL . 'images/reports/' . $img );
}


?>


<div class="groundhogg-report">
    <h2 class="title"><?php _e( 'Email Activity', 'groundhogg' ); ?></h2>
    <canvas id="chart_email_activity"></canvas>
</div>
