<?php

namespace Groundhogg\Admin\Tools;

use Groundhogg\Plugin;
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
            <p><?php echo html()->e( 'a', [
					'class' => 'button',
					'href'  => Plugin::instance()->bulk_jobs->sync_contacts->get_start_url(),
				], __( 'Process', 'groundhogg' ) ) ?></p>
        </div>
    </div>
    <div class="gh-panel">
        <div class="gh-panel-header">
            <h2><?php _e( 'Refresh optin status tags', 'groundhogg' ); ?></h2>
        </div>
        <div class="inside">
            <p><?php _e( 'In the event the optin status tags of your contacts become out of sync, you can re-sync them using this tool.', 'groundhogg' ); ?></p>
            <p><?php echo html()->e( 'a', [
					'class' => 'gh-button secondary',
					'href'  => Plugin::instance()->tag_mapping->get_start_url(),
				], __( 'Process', 'groundhogg' ) ) ?></p>
        </div>
    </div>

    <div class="gh-panel">
        <div class="gh-panel-header">
            <h2 class="hndle"><?php _e( 'Fix birthdays', 'groundhogg' ); ?></h2>
        </div>
        <div class="inside">
            <p><?php _e( 'Fix contact birthday formatting.', 'groundhogg' ); ?></p>
            <p><?php echo html()->e( 'a', [
					'class' => 'button',
					'href'  => Plugin::instance()->bulk_jobs->fix_birthdays->get_start_url(),
				], __( 'Process', 'groundhogg' ) ) ?></p>
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
