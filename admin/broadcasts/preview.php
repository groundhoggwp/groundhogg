<?php

use Groundhogg\Broadcast;
use Groundhogg\Bulk_Jobs\Broadcast_Scheduler;
use function Groundhogg\action_input;
use function Groundhogg\action_url;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_db;
use function Groundhogg\get_url_var;
use function Groundhogg\html;
use function Groundhogg\managed_page_url;
use function Groundhogg\scheduled_time_column;

defined( 'ABSPATH' ) || exit;

$broadcast_id = absint( get_url_var( 'broadcast' ) );

if ( ! $broadcast_id ) {
	exit;
}

$broadcast        = new Broadcast( $broadcast_id );
$query            = $broadcast->get_query();

if ( $broadcast->is_email() ):

	$email = $broadcast->get_object();
	$num_contacts = get_db( 'contacts' )->count( $query );

	?>
    <style>
        .recipients-count {
            font-size: 14px;
            font-family: Georgia, "Times New Roman", "Bitstream Charter", Times, serif;
            padding-right: 6px;
            width: auto;
        }
    </style>
    <table class="form-table">
        <tr>
            <th><?php _e( 'Email', 'groundhogg' ); ?></th>
            <td><a href="<?php echo esc_url( admin_page_url( 'gh_emails', [
					'action' => 'edit',
					'email'  => $email->get_id(),
				] ) ); ?>" target="_blank"><?php esc_html_e( $email->get_title() ); ?></a></td>
        </tr>
        <tr>
            <th><?php _e( 'Subject Line', 'groundhogg' ); ?></th>
            <td><?php esc_html_e( $email->get_subject_line() ); ?></td>
        </tr>
        <tr>
            <th><?php _e( 'Send Time', 'groundhogg' ); ?></th>
            <td>
				<?php echo scheduled_time_column( $broadcast->get_send_time(), false, false, '' ); ?>
                <p class="description"><?php _e( 'When your email will be sent.', 'groundhogg' ); ?></p>
            </td>

        </tr>
        <tr>
            <th><?php _e( 'Total Recipients', 'groundhogg' ); ?></th>
            <td>
                <code><?php esc_html_e( number_format_i18n( $num_contacts ) ); ?></code>
                <p class="description"><?php _e( 'The approximate number of people this email will be sent to.', 'groundhogg' ); ?></p>
            </td>
        </tr>
        <tr>
            <th><?php _e( 'Preview', 'groundhogg' ); ?></th>
            <td><?php echo html()->button( [
					'title' => __( 'Mobile Preview' ),
					'text'  => '<span class="dashicons dashicons-smartphone"></span>',
					'class' => 'button button-secondary dash-button show-email-preview',
				] ); ?>
				<?php echo html()->button( [
					'title' => __( 'Desktop Preview' ),
					'text'  => '<span class="dashicons dashicons-desktop"></span>',
					'class' => 'button button-secondary dash-button show-email-preview',
				] ); ?>
                <p class="description"><?php _e( 'Preview your email on different size devices before sending.', 'groundhogg' ); ?></p>
            </td>
        </tr>
    </table>

	<?php include __DIR__ . '/../emails/preview.php'; ?>
<?php endif;

?>
<form method="post">
	<?php

	wp_nonce_field( 'confirm' );
	action_input( 'confirm' );

	$text = sprintf( _n( 'Send to %s contact!', 'Send to %s contacts', $num_contacts, 'groundhogg' ), number_format_i18n( $num_contacts ) );

	$scheduler    = new Broadcast_Scheduler();
	$confirm_link = $scheduler->get_start_url();
	$cancel_url   = action_url( 'cancel', [
		'broadcast' => $broadcast->get_id(),
	] )

	?>
    <a id="confirm-send" class="button button-primary"
       href="<?php echo esc_url( $confirm_link ); ?>"><?php _e( $text ); ?></a>
    <span id="delete-link"><a class="delete" href="<?php echo esc_url( $cancel_url ); ?>">Cancel</a></span>
</form>
<script>
    (function ($) {

        var confirmed = false;

        $("#confirm-send").click(function (e) {
            confirmed = true;
        });

        $(window).bind("beforeunload", function (event) {
            if (!confirmed) return "Your broadcast has not been scheduled!";
        });
    })(jQuery);
</script>