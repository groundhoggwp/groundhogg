<?php

namespace Groundhogg\Admin\Tools;

use Groundhogg\Plugin;
use function Groundhogg\action_input;
use function Groundhogg\get_request_var;
use function Groundhogg\html;
use function Groundhogg\is_groundhogg_network_active;
use function Groundhogg\white_labeled_name;

?>
	<div id="poststuff">
		<div class="post-box-grid">
			<div class="postbox">
				<div class="postbox-header">
					<h2 class="hndle"><?php _e( 'Refresh optin status tags', 'groundhogg' ); ?></h2>
				</div>
				<div class="inside">
					<p><?php _e( 'In the event the optin status tags of your contacts become out of sync, you can re-sync them using this tool.', 'groundhogg' ); ?></p>
					<p><?php echo html()->e( 'a', [
							'class' => 'button',
							'href'  => Plugin::instance()->tag_mapping->get_start_url(),
						], __( 'Process', 'groundhogg' ) ) ?></p>
				</div>
			</div>
		</div>
	</div>
<?php