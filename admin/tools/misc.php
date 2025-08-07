<?php

namespace Groundhogg\Admin\Tools;

use Groundhogg\Plugin;
use Groundhogg\Tag_Mapping;
use function Groundhogg\action_url;
use function Groundhogg\html;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

?>
<p></p>
<div class="post-box-grid">
    <div class="gh-panel">
        <div class="gh-panel-header">
            <h2 class="hndle"><?php esc_html_e( 'Sync WordPress Users', 'groundhogg' ); ?></h2>
        </div>
        <div class="inside">
            <p><?php esc_html_e( 'Sync any existing WordPress users with your contacts. Meta will be synced based on the general settings.', 'groundhogg' ); ?></p>
            <div class="display-flex gap-10">
				<?php echo html()->e( 'a', [
					'class' => 'button',
					'href'  => action_url( 'sync_users' ),
				], esc_html__( 'Sync Users', 'groundhogg' ) ) ?></p>

				<?php echo html()->e( 'a', [
					'class' => 'button',
					'href'  => action_url( 're_sync_user_ids' ),
				], esc_html__( 'Only re-sync user IDs', 'groundhogg' ) ) ?></p>

            </div>
        </div>
    </div>
	<?php if ( Tag_Mapping::enabled() ): ?>
        <div class="gh-panel">
            <div class="gh-panel-header">
                <h2><?php esc_html_e( 'Refresh opt-in status tags', 'groundhogg' ); ?></h2>
            </div>
            <div class="inside">
                <p><?php esc_html_e( 'In the event the opt-in status tags of your contacts become out of sync, you can re-sync them using this tool.', 'groundhogg' ); ?></p>
                <p><?php echo html()->e( 'a', [
						'class' => 'gh-button secondary',
						'href'  => Plugin::instance()->tag_mapping->get_start_url(),
					], esc_html__( 'Process', 'groundhogg' ) ) ?></p>
            </div>
        </div>
	<?php endif; ?>

	<?php do_action( 'groundhogg/tools/misc' ); ?>

</div>
<script>
  ( ($) => {

    $('.postbox').addClass('gh-panel').removeClass('postbox')
    $('.postbox-header').addClass('gh-panel-header').removeClass('postbox-header')
    $('a.button').addClass('gh-button secondary').removeClass('button')

  } )(jQuery)
</script>
