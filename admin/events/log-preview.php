<?php

use Groundhogg\Email_Log_Item;
use function Groundhogg\get_request_var;

$log_item_id = absint( get_request_var( 'preview' ) );

$log_item = new Email_Log_Item( $log_item_id );

?>
<div class="groundhogg-log-preview">
	<div class="groundhogg-content-box">

		<?php if ( $log_item->exists() ): ?>

			<?php switch ( $log_item->status ):
				default:
				case 'sent': ?>
					<div class="notice notice-success">
						<p><?php _e( 'This email was sent with no issues!', 'groundhogg' ); ?></p>
					</div>
					<?php break;
				case 'failed': ?>
					<div class="notice notice-error">
						<p><?php printf( __( 'This email was was not sent! Error code: <code>%s</code>', 'groundhogg' ), $log_item->error_code ); ?></p>
						<pre><code><?php esc_html_e( $log_item->error_message ); ?></code></pre>
					</div>
					<?php break;
			endswitch; ?>

			<h2><?php _e( 'Details', 'groundhogg' ); ?></h2>
			<table>
				<tbody>
				<tr>
					<th><?php _e( 'Subject:', 'groundhogg' ) ?></th>
					<td><?php esc_html_e( $log_item->subject ); ?></td>
				</tr>
				<tr>
					<th><?php _e( 'From:', 'groundhogg' ) ?></th>
					<td><?php echo \Groundhogg\html()->e( 'a', [
							'href' => \Groundhogg\admin_page_url( 'gh_events', [
								'tab'          => 'emails',
								'from_address' => $log_item->from_address
							] )
						], $log_item->from_address ); ?></td>
				</tr>
				<tr>
					<th><?php _e( 'Recipients:', 'groundhogg' ); ?></th>
					<td><?php

						$links = [];

						foreach ( $log_item->recipients as $recipient ) {

							if ( ! is_email( $recipient ) ) {
								continue;
							}

							$links[] = \Groundhogg\html()->e( 'a', [
								'href' => \Groundhogg\admin_page_url( 'gh_events', [
									'tab' => 'emails',
									's'   => $recipient
								] )
							], $recipient );

						}

						printf( '%s', implode( ', ', $links ) ); ?></td>
				</tr>
				</tbody>
			</table>
			<div id="content">
				<h2><?php _e( 'Content', 'groundhogg' ); ?></h2>

				<iframe id="body-iframe"
				        src="<?php echo \Groundhogg\admin_page_url( 'gh_events', [
					        'action' => 'view_log_content',
					        'log'    => $log_item_id
				        ] ); ?>"></iframe>
			</div>
			<div id="headers">
				<h2><?php _e( 'Headers', 'groundhogg' ); ?></h2>

				<?php
				$headers = "";
				foreach ( $log_item->headers as $header ):
					$headers .= sprintf( "%s: %s\n", $header[0], $header[1] );
				endforeach; ?>

				<textarea class="code" readonly rows="4"><?php esc_html_e( $headers ); ?></textarea>

			</div>

		<?php endif; ?>

	</div>
</div>
