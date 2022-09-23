<?php

namespace Groundhogg\Admin\Reports\Views;

use Groundhogg\Broadcast;
use Groundhogg\Funnel;
use Groundhogg\Plugin;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_db;
use function Groundhogg\get_url_var;
use function Groundhogg\html;

$has_sent_broadcasts = get_db( 'broadcasts' )->exists( [ 'status' => 'sent' ] );


if ( ! $has_sent_broadcasts ):
	?>
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
<?php elseif ( get_url_var( 'broadcast' ) ) : ?>
	<?php include __DIR__ . '/broadcast-single.php' ?>
<?php else: ?>
    <div class="gh-panel">
        <div class="gh-panel-header">
            <h2 class="title"><?php _e( 'All Broadcasts Performance', 'groundhogg' ); ?></h2>
        </div>
        <div id="table_all_broadcasts_performance"></div>
    </div>
<?php endif;

