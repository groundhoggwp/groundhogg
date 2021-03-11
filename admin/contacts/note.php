<?php
namespace Groundhogg\Admin\Contacts;

use Groundhogg\Funnel;
use function Groundhogg\convert_to_local_time;
use function Groundhogg\get_date_time_format;
use function Groundhogg\key_to_words;
use function Groundhogg\time_ago;

/**
 * @var $note \Groundhogg\Classes\Note
 */
switch ( $note->context ) {
	case 'user':
		$user            = get_userdata( absint( $note->user_id ) );
		$display_context = $user ? $user->display_name : __( 'User' );
		break;
	default:
	case 'system':
		$display_context = __( 'System', 'groundhogg' );
		break;
	case 'api':
		$display_context = __( 'API', 'groundhogg' );
		break;
	case 'funnel':
		$funnel_id       = absint( $note->user_id );
		$funnel          = new Funnel( $funnel_id );
		$display_context = $funnel->exists() ? $funnel->get_title() : __( 'Funnel', 'groundhogg' );
		break;
}

$label = __( "Added", 'groundhogg' );

if ( $note->date_created !== date( 'Y-m-d H:i:s', convert_to_local_time( absint( $note->timestamp ) ) ) ) {
	$label = __( 'Last edited', 'groundhogg' );
}

$display_date = time_ago( absint( $note->timestamp ) );
$real_date    = date_i18n( get_date_time_format(), absint( $note->timestamp ) );

?>
<div class="gh-note" id="<?php esc_attr_e( $note->ID ); ?>">
	<div class="gh-note-view">
		<div class="display-notes gh-notes-container">
			<?php echo wpautop( esc_html( $note->content ) ); ?>
		</div>
		<div class='note-actions'>
            <span class="note-date">
            <?php printf( __( '%s by <span class="note-context" title="%s">%s</span> - <abbr title="%s">%s</abbr>', 'groundhogg' ), $label, esc_attr( $display_context ), $display_context, $real_date, $display_date ); ?>
            </span>
			<div class="edit-actions">
                <span class="edit-notes" title="<?php esc_attr_e( 'Edit' ); ?>">
                <a style="text-decoration: none" href="javascript:void(0)">
                    <?php _e( 'Edit' ); ?>
                </a>
            </span> |
				<span class="delete-note delete danger" title="<?php esc_attr_e( 'Delete' ); ?>">
                <a style="text-decoration: none" href="javascript:void(0)">
                    <?php _e( 'Delete' ); ?>
                </a>
            </span>
			</div>
		</div>
	</div>
	<div class="gh-note-edit" style="display: none;">
		<textarea class="edited-note-text"><?php esc_html_e( $note->content ); ?></textarea>
		<a class="button save-note" href="javascript:void(0)"><?php _e( 'Save' ); ?></a>
		<span id="delete-link" class='cancel-note-edit'><a class="delete" href="javascript:void(0)">Cancel</a></span>
		<span class="spinner"></span>
	</div>
	<div class="wp-clearfix"></div>
</div>
