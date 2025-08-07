<?php

namespace Groundhogg\Admin\Reports\Views;

use function Groundhogg\admin_page_url;
use function Groundhogg\get_db;
use function Groundhogg\get_url_var;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

$has_sent_broadcasts = get_db( 'broadcasts' )->exists( [ 'status' => 'sent' ] );

if ( ! $has_sent_broadcasts ):
	?>
    <div class="gh-panel">
        <div class="inside">
            <h1><?php esc_html_e( 'Send your first broadcast!', 'groundhogg' ); ?></h1>
            <p><?php esc_html_e( 'Send a broadcast email to your contacts and then you can see how it performs here!', 'groundhogg' ); ?></p>
            <p><?php esc_html_e( 'Scheduling a broadcast is easy! Have your broadcast scheduled in just a few steps.', 'groundhogg' ); ?></p>
            <ol>
                <li><?php esc_html_e( 'Pick the email template you want to send.', 'groundhogg' ); ?></li>
                <li><?php esc_html_e( 'Pick a date and time you want your contacts to receive it.', 'groundhogg' ); ?></li>
                <li><?php esc_html_e( 'Select which contacts should receive it.', 'groundhogg' ); ?></li>
                <li><b><?php esc_html_e( 'Click send!', 'groundhogg' ); ?></b></li>
            </ol>
            <a class="gh-button primary" href="<?php echo admin_page_url( 'gh_broadcasts', [
				'action' => 'add',
				'type'   => 'email'
			] ) ?>"><?php esc_html_e( 'Schedule a broadcast now!', 'groundhogg' ) ?></a>
        </div>
    </div>
<?php elseif ( get_url_var( 'broadcast' ) ) : ?>
	<?php include __DIR__ . '/broadcast-single.php' ?>
<?php else: ?>
    <div class="display-grid gap-20">
        <div class="gh-panel  span-3">
            <div class="inside">
                <p><b><?php esc_html_e( 'Filter by campaign', 'groundhogg' ) ?></b></p>
                <div id="report-campaign-filter"></div>
            </div>
        </div>
        <div class="gh-panel span-12">
            <div class="gh-panel-header">
                <h2 class="title"><?php esc_html_e( 'All Broadcasts Performance', 'groundhogg' ); ?></h2>
            </div>
            <div id="table_all_broadcasts_performance"></div>
        </div>
    </div>
<?php endif;

