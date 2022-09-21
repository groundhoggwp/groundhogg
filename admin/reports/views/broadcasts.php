<?php

namespace Groundhogg\Admin\Reports\Views;

use Groundhogg\Broadcast;
use Groundhogg\Funnel;
use Groundhogg\Plugin;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_db;
use function Groundhogg\get_url_var;
use function Groundhogg\html;

$broadcasts = get_db( 'broadcasts' );
$broadcasts = $broadcasts->query( [ 'status' => 'sent' ] );

$options = [];

foreach ( $broadcasts as $broadcast ) {
	$broadcast                       = new Broadcast( absint( $broadcast->ID ) );
	$options[ $broadcast->get_id() ] = $broadcast->get_title();
}

if ( ! empty( $broadcasts ) ):
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
		echo html()->dropdown( $args );
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
	<?php do_action( 'groundhogg/admin/reports/pages/broadcast/after_quick_stats' ); ?>
    <div class="groundhogg-chart-wrapper">
        <div class="groundhogg-chart-no-padding" style="width: 100% ; margin-right: 0px;">
            <h2 class="title"><?php _e( 'Broadcast Link Clicked', 'groundhogg' ); ?></h2>
            <div id="table_broadcast_link_clicked"></div>
        </div>
    </div

<?php else: ?>

    <div class="gh-panel">
        <div class="inside">
            <h1><?php _e( 'Send your first broadcast!' ) ?></h1>
            <p><?php _e( 'Send a broadcast email to your contacts and then you can see how it performs here!' ) ?></p>
            <p><?php _e( 'Scheduling a broadcast is easy! Have your broadcast scheduled in just a few steps.' ) ?></p>
            <ol>
                <li><?php _e( 'Pick the email template you want to send.' ) ?></li>
                <li><?php _e( 'Pick a date and time you want your contacts to receive it.' ) ?></li>
                <li><?php _e( 'Select which contacts should receive it.' ) ?></li>
                <li><b><?php _e( 'Click send!' ) ?></b></li>
            </ol>
            <a class="gh-button primary" href="<?php echo admin_page_url( 'gh_broadcasts', [
				'action' => 'add',
				'type'   => 'email'
			] ) ?>"><?php _e( 'Schedule a broadcast now!', 'groundhogg' ) ?></a>
        </div>
    </div>

<?php endif;

