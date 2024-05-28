<?php

namespace Groundhogg\Admin\Contacts;


use Groundhogg\Contact;
use function Groundhogg\dashicon_e;
use function Groundhogg\get_post_var;
use function Groundhogg\html;
use function Groundhogg\is_a_contact;
use function Groundhogg\isset_not_empty;
use function Groundhogg\verify_admin_ajax_nonce;

class Info_Cards {

	public function __construct() {
		add_action( 'admin_init', [ $this, 'register_core_cards' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'scripts' ] );
		add_action( 'wp_ajax_groundhogg_save_card_order', [ $this, 'save_card_atts' ] );
	}

	public function scripts() {
		wp_enqueue_style( 'groundhogg-admin-contact-info-cards' );
		wp_enqueue_script( 'groundhogg-admin-contact-info-cards' );
		wp_enqueue_style( 'buttons' );
		wp_enqueue_style( 'media-views' );
		wp_enqueue_script( 'postbox' );
	}

	/**
	 * Register the info cards as metaboxes on the screen so they show as screen options
	 */
	public static function register_as_metaboxes_for_screen_options() {
		global $wp_meta_boxes;

		$wp_meta_boxes['groundhogg_page_gh_contacts']['side']['core'] = self::$info_cards;
	}

	/**
	 * Save the user card info to the user meta when they change the order of the cards in the UI
	 */
	public function save_card_atts() {
		if ( ! current_user_can( 'view_contacts' ) || ! verify_admin_ajax_nonce() ) {
			return;
		}

		$card_order = array_map( function ( $card_atts ) {
			return [
				'id'     => sanitize_key( $card_atts['id'] ),
				'open'   => $card_atts['open'] !== 'false',
				'hidden' => $card_atts['hidden'] !== 'false',
			];
		}, get_post_var( 'cardOrder', [] ) );

		update_user_meta( get_current_user_id(), 'groundhogg_info_card_order', $card_order );

        wp_send_json_success();
	}

	/**
	 * Register the core cards
	 */
	public function register_core_cards() {

		self::register( 'user', __( 'WordPress User', 'groundhogg' ), function ( $contact ) {
			include __DIR__ . '/cards/user.php';
		}, 100, 'edit_users' );

		self::register( 'page_visits', __( 'Page Visits', 'groundhogg' ), function ( $contact ) {
			include __DIR__ . '/cards/page-visits.php';
		}, 100, 'view_contacts' );

		do_action( 'groundhogg/admin/contacts/register_info_cards', $this );

//		self::register_as_metaboxes_for_screen_options();
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
	 * @param string   $id                      the ID of the info card
	 * @param string   $title                   the title of the info card
	 * @param callable $callback                callback to display the data
	 * @param int      $priority                how high in the cards it should be displayed
	 * @param string   $capability              the minimum capability for the viewing user to see the data in this card.
	 * @param callable $should_display_callback an optional function you can define that will conditionally show the info card based on external parameters. Returns true or false. Returning false will hide the card.
	 */
	public static function register( $id, $title, $callback, $priority = 100, $capability = 'view_contacts', $should_display_callback = null ) {

		if ( empty( $id ) || ! is_callable( $callback ) ) {
			return;
		}

		self::$info_cards[ sanitize_key( $id ) ] = [
			'id'                      => sanitize_key( $id ),
			'title'                   => $title,
			'callback'                => $callback,
			'priority'                => $priority,
			'capability'              => $capability,
			'should_display_callback' => $should_display_callback,
			'args'                    => [] // metabox compat only
		];
	}

	/**
	 * Unregister unwanted cards
	 *
	 * @param $id
	 */
	public static function unregister( $id ) {
		unset( self::$info_cards[ $id ] );
	}

	/**
	 * Get the cards according to the current preferences of the user.
	 *
	 * @return array[]
	 */
	public static function get_user_info_cards() {

		$cards = self::$info_cards;

		$user_info_card_atts = get_user_meta( get_current_user_id(), 'groundhogg_info_card_order', true );
		$priority            = 0;

		if ( ! empty( $user_info_card_atts ) && is_array( $user_info_card_atts ) ) {
			foreach ( $user_info_card_atts as $card_atts ) {

				$card_atts = wp_parse_args( $card_atts, [
					'id'     => '',
					'open'   => true,
					'hidden' => false,
				] );

				if ( ! isset_not_empty( $cards, $card_atts['id'] ) ) {
					continue;
				}

				$cards[ $card_atts['id'] ]['priority'] = $priority;
				$cards[ $card_atts['id'] ]['open']     = $card_atts['open'];
				$cards[ $card_atts['id'] ]['hidden']   = $card_atts['hidden'];

				$priority += 100;
			}
		}

		// Override any existing priority with the users.

		// Sort the meta boxes by priority
		uasort( $cards, function ( $a, $b ) {
			return $a['priority'] - $b['priority'];
		} );

		$cards = array_filter( $cards, function ( $card ) {
			return current_user_can( $card['capability'] );
		} );

		return $cards;
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

		$cards = self::get_user_info_cards();

		foreach ( $cards as $info_card ):

			$info_card = wp_parse_args( $info_card, [
				'priority' => 100,
				'open'     => true,
				'hidden'   => false,
			] );

			/**
			 * @var int      $id
			 * @var string   $title
			 * @var callable $callback
			 * @var int      $priority
			 * @var string   $capability
			 * @var bool     $open
			 * @var bool     $hidden
			 * @var callable $should_display_callback
			 */

			extract( $info_card, EXTR_OVERWRITE );

			if ( ! current_user_can( $capability ) || ( is_callable( $should_display_callback ) && ! call_user_func( $should_display_callback, $contact ) ) ) {
				continue;
			}

			?>
            <div id="<?php esc_attr_e( $id ); ?>"
                 class="gh-panel info-card <?php esc_attr_e( $id ); ?> <?php esc_attr_e( ! $open ? 'closed' : '' ); ?> <?php esc_attr_e( $hidden ? 'hidden' : '' ); ?>">
                <div class="gh-panel-header">
                    <h2><?php echo $title; ?></h2>
                    <div class="actions hide-if-no-js">
                        <button type="button" class="panel-handle-order-higher" aria-disabled="false"
                                aria-describedby="<?php esc_attr_e( $id ); ?>-handle-order-higher-description">
                            <span class="screen-reader-text"><?php _e( 'Move up' ); ?></span>
                        </button>
                        <button type="button" class="panel-handle-order-lower" aria-disabled="false"
                                aria-describedby="<?php esc_attr_e( $id ); ?>-handle-order-lower-description">
                            <span class="screen-reader-text"><?php _e( 'Move down' ); ?></span>
                        </button>
                        <button type="button" class="toggle-indicator" aria-expanded="true">
                                <span class="screen-reader-text">
                                    <?php _e( 'Toggle info box panel', 'groundhogg' ); ?>
                                </span>
                        </button>
                    </div>
                </div>
                <div class="inside">
					<?php call_user_func( $callback, $contact ); ?>
					<?php do_action( "groundhogg/admin/contact/info_card/{$id}", $contact ); ?>
                </div>
            </div>
		<?php
		endforeach;

	}

	/**
	 * Output the whole info card module
	 *
	 * @param $contact
	 */
	public static function display( $contact ) {
		?>
        <div class="info-cards-wrap">
            <div class="info-card-actions postbox gh-panel">
                <div class="inside">
                    <a class="expand-all"
                       href="javascript:void(0)"><?php _e( 'Expand All', 'groundhogg' ); ?><?php dashicon_e( 'arrow-up' ); ?></a>
                    <a class="collapse-all"
                       href="javascript:void(0)"><?php _e( 'Collapse All', 'groundhogg' ); ?><?php dashicon_e( 'arrow-down' ); ?></a>
                    <a class="view-cards"
                       href="javascript:void(0)"><?php _e( 'Cards', 'groundhogg' ); ?><?php dashicon_e( 'visibility' ); ?></a>
                </div>
            </div>
            <div class="info-card-views gh-panel hidden">
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
            <div class="info-cards-sortables">
				<?php Info_Cards::do_info_cards( $contact ); ?>
            </div>
        </div>
		<?php
	}

}
