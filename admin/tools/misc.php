<?php

namespace Groundhogg\Admin\Tools;

use Groundhogg\Plugin;
use function Groundhogg\action_url;
use function Groundhogg\html;

?>
<p></p>
<div class="post-box-grid">
    <div class="gh-panel">
        <div class="gh-panel-header">
            <h2 class="hndle"><?php _e( 'Sync WordPress Users', 'groundhogg' ); ?></h2>
        </div>
        <div class="inside">
            <p><?php _e( 'Sync any existing WordPress users with your contacts. Meta will be synced based on the general settings.', 'groundhogg' ); ?></p>
            <div class="display-flex gap-10">
		        <?php echo html()->e( 'a', [
			        'class' => 'button',
			        'href'  => Plugin::instance()->bulk_jobs->sync_contacts->get_start_url(),
		        ], __( 'Sync Users', 'groundhogg' ) ) ?></p>

	            <?php echo html()->e( 'a', [
		            'class' => 'button',
		            'href'  => action_url( 're_sync_user_ids' ),
	            ], __( 'Only re-sync user IDs', 'groundhogg' ) ) ?></p>

            </div>
        </div>
    </div>
    <div class="gh-panel">
        <div class="gh-panel-header">
            <h2><?php _e( 'Refresh opt-in status tags', 'groundhogg' ); ?></h2>
        </div>
        <div class="inside">
            <p><?php _e( 'In the event the opt-in status tags of your contacts become out of sync, you can re-sync them using this tool.', 'groundhogg' ); ?></p>
            <p><?php echo html()->e( 'a', [
					'class' => 'gh-button secondary',
					'href'  => Plugin::instance()->tag_mapping->get_start_url(),
				], __( 'Process', 'groundhogg' ) ) ?></p>
        </div>
    </div>
    <div class="gh-panel">
        <div class="gh-panel-header">
            <h2 class="hndle"><?php _e( 'Restore missing funnel events', 'groundhogg' ); ?></h2>
        </div>
        <div class="inside">
            <p><?php _e( 'If funnel events get cancelled or deleted, this tool will restore contacts to their most recent position in any funnels they were active in within the last 30 days.', 'groundhogg' ); ?></p>
            <p><?php echo html()->e( 'a', [
					'class' => 'gh-button danger',
					'href'  => action_url( 'restore_funnel_events' ),
				], __( 'Restore', 'groundhogg' ) ) ?></p>
        </div>
    </div>

	<?php do_action( 'groundhogg/tools/misc' ); ?>

</div>
<script>
  (($) => {

    $('.postbox').addClass('gh-panel').removeClass('postbox')
    $('.postbox-header').addClass('gh-panel-header').removeClass('postbox-header')
    $('a.button').addClass('gh-button secondary').removeClass('button')

  })(jQuery)
</script>
