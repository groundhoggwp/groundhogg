<?php

namespace Groundhogg\Admin\Contacts;


use Groundhogg\Contact;
use function Groundhogg\get_post_var;
use function Groundhogg\is_a_contact;
use function Groundhogg\isset_not_empty;

class Info_Cards {

	public function __construct() {
		add_action( 'admin_init', [ $this, 'register_core_cards' ] );
		add_action( 'wp_ajax_groundhogg_save_card_order', [ $this, 'save_card_atts' ] );
	}

	/**
	 * Save the user card info to the user meta when they change the order of the cards in the UI
	 */
	public function save_card_atts() {
		if ( ! current_user_can( 'view_contacts' ) ) {
			return;
		}

		$card_order = array_map( function ( $card_atts ) {
			return [
				'id'   => sanitize_key( $card_atts['id'] ),
				'open' => $card_atts['open'] !== 'false',
			];
		}, get_post_var( 'cardOrder', [] ) );

		update_user_meta( get_current_user_id(), 'groundhogg_info_card_order', $card_order );
	}

	/**
	 * Register the core cards
	 */
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
	public static $info_cards = [];

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

		self::$info_cards[ sanitize_key( $id ) ] = [
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

		$user_info_card_atts = get_user_meta( get_current_user_id(), 'groundhogg_info_card_order', true );
		$priority            = 0;

		if ( ! empty( $user_info_card_atts ) && is_array( $user_info_card_atts ) ) {
			foreach ( $user_info_card_atts as $card_atts ) {

				$card_atts = wp_parse_args( $card_atts, [
					'id'   => '',
					'open' => true,
				] );

				if ( ! isset_not_empty( self::$info_cards, $card_atts['id'] ) ) {
					continue;
				}

				self::$info_cards[ $card_atts['id'] ]['priority'] = $priority;
				self::$info_cards[ $card_atts['id'] ]['open']     = $card_atts['open'];

				$priority += 100;
			}
		}

		// Override any existing priority with the users.

		// Sort the meta boxes by priority
		usort( self::$info_cards, function ( $a, $b ) {
			return $a['priority'] - $b['priority'];
		} );

		foreach ( self::$info_cards as $info_card ):

			$info_card = wp_parse_args( $info_card, [
				'priority' => 100,
				'open'     => true,
			] );

			/**
			 * @var int $id
			 * @var string $title
			 * @var string $icon
			 * @var callable $callback
			 * @var int $priority
			 * @var string $capability
			 * @var bool $open
			 */

			extract( $info_card, EXTR_OVERWRITE );

			if ( current_user_can( $capability ) ): ?>
                <div id="<?php esc_attr_e( $id ); ?>"
                     class="postbox info-card <?php esc_attr_e( $id ); ?> <?php esc_attr_e( ! $open ? 'closed' : '' ); ?>">
                    <div class="postbox-header">
                        <h2 class="hndle"><?php echo $title; ?></h2>
                        <div class="handle-actions hide-if-no-js">
                            <button type="button" class="handle-order-higher" aria-disabled="false"
                                    aria-describedby="<?php esc_attr_e( $id ); ?>-handle-order-higher-description">
                                <span class="screen-reader-text"><?php _e( 'Move up' ); ?></span>
                                <span class="order-higher-indicator" aria-hidden="true"></span>
                            </button>
                            <span class="hidden" id="pageparentdiv-handle-order-higher-description">
                                <?php _e( 'Move info box up', 'groundhogg' ); ?>
                            </span>
                            <button type="button" class="handle-order-lower" aria-disabled="false"
                                    aria-describedby="<?php esc_attr_e( $id ); ?>-handle-order-lower-description">
                                <span class="screen-reader-text"><?php _e( 'Move down' ); ?></span>
                                <span class="order-lower-indicator" aria-hidden="true"></span>
                            </button>
                            <span class="hidden" id="<?php esc_attr_e( $id ); ?>-handle-order-lower-description">
                                <?php _e( 'Move info box down', 'grounhogg' ); ?>
                            </span>
                            <button type="button" class="handlediv" aria-expanded="true">
                                <span class="screen-reader-text">
                                    <?php _e( 'Toggle info box panel', 'grounhogg' ); ?>
                                </span>
                                <span class="toggle-indicator" aria-hidden="true"></span>
                            </button>
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
