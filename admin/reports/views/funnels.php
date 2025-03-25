<?php

namespace Groundhogg\Admin\Reports\Views;

// Overview
use function Groundhogg\admin_page_url;
use function Groundhogg\get_db;
use function Groundhogg\get_url_var;

$has_active_funnels = get_db( 'funnels' )->exists( [ 'status' => 'active' ] );

if ( ! $has_active_funnels ): ?>
    <div class="gh-panel">
        <div class="inside">
            <h1><?php _e( 'Create your first flow!' ) ?></h1>
            <p><?php _e( 'Create a flow then come here to track its performance!' ) ?></p>
            <p><?php _e( 'Creating a flow is easy, just follow the steps below...' ) ?></p>
            <ol>
                <li><?php _e( 'Start with a flow template for what you want to achieve.' ) ?></li>
                <li><?php _e( 'Tweak any email copy or notification settings.' ) ?></li>
                <li><?php _e( 'Embed any forms on your landing pages...' ) ?></li>
                <li><b><?php _e( 'Activate your flow!' ) ?></b></li>
            </ol>
            <a class="gh-button primary" href="<?php echo admin_page_url( 'gh_funnels', [
				'action' => 'add',
			] ) ?>"><?php _e( 'Create a flow now!', 'groundhogg' ) ?></a>
        </div>
    </div>
<?php elseif ( get_url_var( 'funnel' ) ):

	include __DIR__ . '/funnel-single.php';

elseif ( get_url_var( 'step' ) ):

	include __DIR__ . '/email-step.php';

else: ?>

    <div class="display-grid gap-20">
        <div class="gh-panel  span-3">
            <div class="inside">
                <p><b><?php _e( 'Filter by campaign' ) ?></b></p>
                <div id="report-campaign-filter"></div>
            </div>
        </div>
        <div class="gh-panel span-12">
            <div class="gh-panel-header">
                <h2 class="title">
					<?php _e( 'All Flows Performance', 'groundhogg' ); ?>
                    <span class="gh-has-tooltip dashicons dashicons-info">
                    <span class="gh-tooltip top">
                        <?php _e( '<b>Added:</b> The number of contacts that were added to the flow.', 'groundhogg' ) ?><br/><br/>
                        <?php _e( '<b>Active:</b> The number of contacts that were completed any step within the flow during the time range.', 'groundhogg' ) ?>
                    </span>
                </span>
                </h2>
            </div>
            <div id="table_all_funnels_performance"></div>
        </div>
    </div>

<?php endif;
