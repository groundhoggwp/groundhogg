<?php

namespace Groundhogg\Admin\Contacts;


use Groundhogg\Contact;
use function Groundhogg\dashicon;
use function Groundhogg\is_a_contact;

class Info_Cards {

	public function __construct() {
		add_action( 'admin_init', [ $this, 'register_core_cards' ] );
	}

	public function register_core_cards() {

		self::register( 'user', __( 'WordPress User', 'groundhogg' ), '', function ( $contact ) {
			include __DIR__ . '/cards/user.php';
		} );

		self::register( 'files', __( 'Files', 'groundhogg' ), '', '__return_empty_string' );
		self::register( 'activity', __( 'Activity', 'groundhogg' ), '', '__return_empty_string' );

		do_action( 'groundhogg/admin/contacts/register_info_cards', $this );
	}

	/**
	 * Static memory of the meta boxes
	 *
	 * @var array[]
	 */
	public static $info_boxes = [];

	/**
	 * Register a new info box.
	 *
	 * @param int $id
	 * @param string $title
	 * @param string $icon
	 * @param callable $callback
	 * @param int $priority
	 * @param string $capability
	 */
	public static function register( $id, $title, $icon, $callback, $priority = 100, $capability = 'view_contacts' ) {

		if ( empty( $id ) || ! is_callable( $callback ) ) {
			return;
		}

		self::$info_boxes[ sanitize_key( $id ) ] = [
			'id'         => sanitize_key( $id ),
			'title'      => $title,
			'icon'       => $icon,
			'callback'   => $callback,
			'priority'   => $priority,
			'capability' => $capability
		];
	}

	/**
	 * Output the contact info boxes
	 *
	 * @param $contact Contact
	 */
	public static function do_info_cards( $contact ) {

		if ( ! is_a_contact( $contact ) ) {
			return;
		}

		// Sort the meta boxes by priority
		usort( self::$info_boxes, function ( $a, $b ) {
			return $a['priority'] - $b['priority'];
		} );

		foreach ( self::$info_boxes as $info_box ):

			/**
			 * @var int $id
			 * @var string $title
			 * @var string $icon
			 * @var callable $callback
			 * @var int $priority
			 * @var string $capability
			 */

			extract( $info_box, EXTR_OVERWRITE );

			if ( current_user_can( $capability ) ): ?>
                <div id=<?php esc_attr_e( $id ); ?>"" class="postbox info-card <?php esc_attr_e( $id ); ?>">
                    <div class="postbox-header">
                        <h2 class="hndle ui-sortable-handle"><?php echo $title; ?></h2>
                        <div class="handle-actions hide-if-no-js">
                            <button type="button" class="handle-order-higher" aria-disabled="false"
                                    aria-describedby="pageparentdiv-handle-order-higher-description"><span
                                        class="screen-reader-text"><?php _e( 'Move up' ); ?></span><span
                                        class="order-higher-indicator"
                                        aria-hidden="true"></span></button>
                            <span class="hidden"
                                  id="pageparentdiv-handle-order-higher-description"><?php _e( 'Move info box up', 'groundhogg' ); ?></span>
                            <button type="button" class="handle-order-lower" aria-disabled="false"
                                    aria-describedby="pageparentdiv-handle-order-lower-description"><span
                                        class="screen-reader-text"><?php _e( 'Move down' ); ?></span><span
                                        class="order-lower-indicator"
                                        aria-hidden="true"></span></button>
                            <span class="hidden"
                                  id="pageparentdiv-handle-order-lower-description"><?php _e( 'Move info box down', 'grounhogg' ); ?></span>
                            <button type="button" class="handlediv" aria-expanded="true"><span
                                        class="screen-reader-text"><?php _e( 'Toggle info box panel', 'grounhogg' ); ?></span><span
                                        class="toggle-indicator" aria-hidden="true"></span></button>
                        </div>
                    </div>
                    <div class="inside">
						<?php call_user_func( $callback, $contact ); ?>
						<?php do_action( "groundhogg/admin/contact/info_card/{$id}", $contact ); ?>
                    </div>
                </div>
			<?php endif;
		endforeach;

	}


}
