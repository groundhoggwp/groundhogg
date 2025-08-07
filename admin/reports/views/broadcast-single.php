<?php

namespace Groundhogg\Admin\Reports\Views;

use Groundhogg\Broadcast;
use function Groundhogg\get_url_var;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

$broadcast = new Broadcast( get_url_var( 'broadcast' ) );


?>
<div class="display-flex gap-20 align-center">
    <h1 class="report-title"><?php echo esc_html( $broadcast->get_title() ) ?></h1>
</div>
<div class="display-grid gap-20">
    <div class="gh-panel span-6">
        <div class="gh-panel-header">
            <h2 class="title"><?php esc_html_e( 'Broadcast Stats', 'groundhogg' ); ?></h2>
        </div>
        <div class="inside">
            <canvas id="chart_last_broadcast"></canvas>
        </div>
    </div>
    <div class="gh-panel span-6">
        <div class="gh-panel-header">
            <h2 class="title"><?php esc_html_e( 'Results', 'groundhogg' )); ?></h2>
        </div>
        <div id="table_broadcast_stats"></div>
    </div>

	<?php do_action( 'groundhogg/admin/reports/pages/broadcast/after_quick_stats' ); ?>

    <div class="gh-panel span-12">
        <div class="gh-panel-header">
            <h2 class="title"><?php esc_html_e( 'Broadcast Link Clicked', 'groundhogg' )); ?></h2>
        </div>
        <div id="table_broadcast_link_clicked"></div>
    </div
</div>
