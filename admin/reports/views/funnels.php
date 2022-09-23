<?php

namespace Groundhogg\Admin\Reports\Views;

// Overview
use Groundhogg\Funnel;
use Groundhogg\Plugin;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_cookie;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use function Groundhogg\get_url_var;
use function Groundhogg\html;

$has_active_funnels = get_db( 'funnels' )->exists( [ 'status' => 'active' ] );

if ( ! $has_active_funnels ): ?>
    <div class="gh-panel">
        <div class="inside">
            <h1><?php _e( 'Launch your first funnel!' ) ?></h1>
            <p><?php _e( 'Launch a funnel then come here to track its peformace!' ) ?></p>
            <p><?php _e( 'Creating a funnel is easy, just follow the steps below...' ) ?></p>
            <ol>
                <li><?php _e( 'Start with a funnel template for what you want to achieve.' ) ?></li>
                <li><?php _e( 'Tweak any email copy or notification settings.' ) ?></li>
                <li><?php _e( 'Embed any forms on your landing pages...' ) ?></li>
                <li><b><?php _e( 'Activate your funnel!' ) ?></b></li>
            </ol>
            <a class="gh-button primary" href="<?php echo admin_page_url( 'gh_funnels', [
				'action' => 'add',
			] ) ?>"><?php _e( 'Launch a funnel now!', 'groundhogg' ) ?></a>
        </div>
    </div>
<?php elseif ( get_url_var( 'funnel' ) ):

	include __DIR__ . '/funnel-single.php';

elseif ( get_url_var( 'step' ) ):

	include __DIR__ . '/email-step.php';

else: ?>

    <div class="gh-panel">
        <div class="gh-panel-header">
            <h2 class="title"><?php _e( 'All Funnel Performance', 'groundhogg' ); ?></h2>
        </div>
        <div id="table_all_funnels_performance"></div>
    </div>

<?php endif;
