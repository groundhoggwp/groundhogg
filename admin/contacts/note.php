<?php
namespace Groundhogg\Admin\Contacts;

use function Groundhogg\convert_to_local_time;
use function Groundhogg\get_date_time_format;
use function Groundhogg\key_to_words;

/**
 * @var $note \Groundhogg\Classes\Note
 */

$context = key_to_words( $note->context );

if ( absint( $note->user_id ) ) {
	$user    = get_userdata( absint( $note->user_id ) );
	$context = sprintf( '%s', $user->display_name );
}

$label = __( "Added", 'groundhogg' );

if ( $note->date_created !== date( 'Y-m-d H:i:s', convert_to_local_time( absint( $note->timestamp ) ) ) ) {
	$label = __( 'Last edited', 'groundhogg' );
}

?>
<div class="gh-note" id="<?php esc_attr_e( $note->ID ); ?>">
    <div class="gh-note-view">
        <div class='note-actions'>
            <span class="note-date">
            <?php _e( sprintf( '%s by %s on %s', $label, $context, date_i18n( get_date_time_format(), convert_to_local_time( absint( $note->timestamp ) ) ) ), 'groundhogg' ) ?>
            </span>
             | <span class="edit-notes">
                <a style="text-decoration: none" href="javascript:void(0)">
                    <span class="dashicons dashicons-edit"></span>
                </a>
            </span>
             | <span class="delete-note">
                <a style="text-decoration: none" href="javascript:void(0)">
                    <span class="dashicons dashicons-trash delete"></span>
                </a>
            </span>
        </div>
        <div class="display-notes gh-notes-container">
		    <?php echo wpautop( esc_html( $note->content ) ); ?>
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
