<?php

namespace Groundhogg\Admin\Settings;

use function Groundhogg\get_url_var;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The settings section for receiving messages, on the Email tab. This just gives the section and a place for the UI
 * to mount into, the UI itself (status, and the buttons to provision, rotate, update and disconnect) is JS, talking
 * to Inbox_Api. That's so the state (has it been set up, is it this site's, ...) can be read and acted on without a
 * full page reload, the way the rest of Groundhogg's settings-like screens work.
 *
 * @see \Groundhogg\Api\V4\Inbox_Api the REST API this drives
 * @see \Groundhogg\Classes\Inbox_Client what the API calls
 */
class Incoming_Messages {

	const PAGE = 'gh_settings';
	const MOUNT_ID = 'incoming-messages-settings';

	public static function init() {
		add_filter( 'groundhogg/admin/settings/sections', [ self::class, 'add_section' ] );
		add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue' ] );
	}

	/**
	 * Ahead of Outgoing Email - this is what a contact sees before they ever get an email from the site, whereas
	 * outgoing email is what the site sends after this is already set up.
	 *
	 * @param array $sections
	 *
	 * @return array
	 */
	public static function add_section( $sections ) {

		$incoming_messages = [
			'id'       => 'incoming_messages',
			'title'    => _x( 'Incoming Messages', 'settings_sections', 'groundhogg' ),
			'tab'      => 'email',
			'callback' => [ self::class, 'render' ],
		];

		$keys     = array_keys( $sections );
		$position = array_search( 'outgoing_email_config', $keys, true );

		// no such section (an add-on removed it, or it moved) - put this one first rather than lose it
		if ( $position === false ) {
			return [ 'incoming_messages' => $incoming_messages ] + $sections;
		}

		return array_slice( $sections, 0, $position, true )
		       + [ 'incoming_messages' => $incoming_messages ]
		       + array_slice( $sections, $position, null, true );
	}

	public static function enqueue() {

		if ( get_url_var( 'page' ) !== self::PAGE || get_url_var( 'tab', 'general' ) !== 'email' ) {
			return;
		}

		wp_enqueue_script( 'groundhogg-admin-incoming-messages' );
	}

	/**
	 * The section. A card, and a mount point inside it that the JS renders into.
	 */
	public static function render() {
		?>
        <div class="gh-panel outlined incoming-messages-panel">
            <div class="inside">
                <p class="description" style="margin-top: 0;">
					<?php esc_html_e( 'Receive replies from your contacts, and keep a copy of what you send from your own mailbox, in the Messages tab of the contact.', 'groundhogg' ); ?>
                </p>
                <div id="<?php echo esc_attr( self::MOUNT_ID ); ?>"></div>
            </div>
        </div>
		<?php
	}
}
