<?php
namespace Groundhogg\Admin\Contacts;

// Exit if accessed directly
use Groundhogg\Plugin;
use function Groundhogg\current_user_is;
use function Groundhogg\dashicon_e;
use function Groundhogg\get_request_var;
use function Groundhogg\html;
use function Groundhogg\isset_not_empty;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$id = absint( get_request_var( 'contact' ) );

$contact = Plugin::$instance->utils->get_contact( $id );

if ( ! $contact || ! $contact->exists() ) {
	wp_die( _x( 'This contact has been deleted.', 'contact_record', 'groundhogg' ) );
}

/* Quit if */
if ( current_user_is( 'sales_manager' ) ) {
	if ( $contact->get_owner_id() !== get_current_user_id() ) {
		wp_die( _x( 'You are not the owner of this contact.', 'contact_record', 'groundhogg' ) );
	}
}

?>
<div class="contact-record">
    <div class="contact-editor-wrap">
		<?php include __DIR__ . '/contact-editor.php'; ?>
    </div>
    <div class="contact-info-cards meta-box-sortables">
        <div class="info-card-actions">
            <a class="expand-all"
               href="javascript:void(0)"><?php _e( 'Expand All', 'groundhogg' ); ?><?php dashicon_e( 'arrow-up' ); ?></a>
            <a class="collapse-all"
               href="javascript:void(0)"><?php _e( 'Collapse All', 'groundhogg' ); ?><?php dashicon_e( 'arrow-down' ); ?></a>
            <a class="view-cards" href="javascript:void(0)"><?php _e( 'Cards', 'groundhogg' ); ?><?php dashicon_e( 'visibility' ); ?></a>
        </div>
        <div class="info-card-views postbox hidden">
            <div class="inside">
                <p><?php _e( 'Select which cards you want visible.', 'groundhogg' ); ?></p>
                <ul>
				<?php

				foreach ( Info_Cards::get_user_info_cards() as $id => $card ):

					?>
                    <li><?php
					echo html()->checkbox( [
						'label'   => $card['title'],
						'name'    => sprintf( 'cards_display[%s]', $id ),
						'class'   => 'hide-card',
						'value'   => $id,
						'checked' => ! isset_not_empty( $card, 'hidden' )
					] );
					?></li><?php

				endforeach;

				?>
                </ul>
                <p>
                    <a class="view-cards" href="javascript:void(0)"><?php _e( 'Close', 'groundhogg' ); ?></a>
                </p>
            </div>
        </div>
		<?php Info_Cards::do_info_cards( $contact ); ?>
    </div>
</div>
