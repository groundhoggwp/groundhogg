<?php

namespace Groundhogg\Admin\Contacts;

use Composer\Package\Package;
use Groundhogg\Contact;
use function Groundhogg\is_a_contact;

class Info_Boxes {

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
	public function do_info_boxes( $contact ) {

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
                <div class="postbox <?php esc_attr_e( $id ); ?>">
                    <h3 class="hndle"><?php echo $title; ?></h3>
                    <div class="inside">
						<?php call_user_func( $callback, $contact ); ?>
                    </div>
                </div>
			<?php endif;
		endforeach;

	}


}
