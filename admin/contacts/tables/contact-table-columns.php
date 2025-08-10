<?php

namespace Groundhogg\Admin\Contacts\Tables;

use Groundhogg\Classes\Activity;
use Groundhogg\Contact;
use Groundhogg\Plugin;
use Groundhogg\Preferences;
use Groundhogg\Tag;
use Groundhogg\Utils\DateTimeHelper;
use function Groundhogg\admin_page_url;
use function Groundhogg\dashicon_e;
use function Groundhogg\get_array_var;
use function Groundhogg\get_gh_page_screen_id;
use function Groundhogg\get_unsub_reasons;
use function Groundhogg\html;
use function Groundhogg\scheduled_time_column;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class Contact_Table_Columns {

	public function __construct() {
		add_action( 'admin_init', [ $this, 'register_core_columns' ] );
		add_action( 'groundhogg_contacts_custom_column', [ $this, 'column_callback' ], 10, 2 );

		add_filter( 'groundhogg_contact_columns', [ $this, 'add_columns_to_table' ] );
		add_filter( 'groundhogg_contact_sortable_columns', [ $this, 'add_sortable_columns_to_table' ] );

		$screen = get_gh_page_screen_id( 'gh_contacts' );

		add_filter( "manage_{$screen}_columns", [ $this, 'add_columns_to_screen_options' ] );
		add_filter( 'groundhogg/contact_query/allowed_orderby_keys', [ $this, 'add_orderby_keys' ] );
	}

	/**
	 * Add defined orderby keys as allowed keys in Contact_Query
	 *
	 * @param $keys
	 *
	 * @return array
	 */
	public function add_orderby_keys( $keys ) {

		$orderby_keys = array_filter( wp_list_pluck( self::$columns, 'orderby' ) );

		return array_unique( array_merge( $keys, $orderby_keys ) );
	}

	/**
	 * Sort the columns based on priority
	 *
	 * @return void
	 */
	public function sort_columns() {
		// Sort the meta boxes by priority
		uasort( self::$columns, function ( $a, $b ) {
			return $a['priority'] - $b['priority'];
		} );
	}

	/**
	 * Add the columns to the contacts table
	 *
	 * @param $columns
	 *
	 * @return mixed
	 */
	public function add_columns_to_table( $columns ) {

		$this->sort_columns();

		foreach ( self::$columns as $column ) {

			if ( ! current_user_can( $column['capability'] ) ) {
				continue;
			}

			$columns[ $column['id'] ] = $column['title'];
		}

		return $columns;
	}

	/**
	 * Add the sortable columns to the contacts table
	 *
	 * @param $columns
	 *
	 * @return mixed
	 */
	public function add_sortable_columns_to_table( $columns ) {

		foreach ( self::$columns as $column ) {

			if ( ! current_user_can( $column['capability'] ) || ! $column['orderby'] ) {
				continue;
			}

			$columns[ $column['id'] ] = [ $column['orderby'], false ];
		}

		return $columns;
	}

	/**
	 * Add the columns to the screen options
	 *
	 * @param $columns
	 *
	 * @return mixed
	 */
	public function add_columns_to_screen_options( $columns ) {

		$this->sort_columns();

		foreach ( self::$columns as $column ) {

			if ( ! current_user_can( $column['capability'] ) ) {
				continue;
			}

			$columns[ $column['id'] ] = $column['title'];
		}

		return $columns;
	}

	/**
	 * Static memory of the meta boxes
	 *
	 * @var array[]
	 */
	public static $columns = [];

	/**
	 * All the presets
	 *
	 * @var array
	 */
	public static $presets = [];

	/**
	 * Register a preset
	 *
	 * @param string $id
	 * @param string $title
	 *
	 * @return void
	 */
	public static function register_preset( string $id, string $title ) {
		self::$presets[ $id ] = $title;
	}

	/**
	 * Get list of visible presets
	 *
	 * @return array
	 */
	public static function get_presets() {

		$presets = [];

		foreach ( self::$presets as $id => $name ) {

			$columns = array_map( function ( $column ) {
				return $column['id'];
			}, array_filter( self::$columns, function ( $column ) use ( $id ) {

				if ( ! current_user_can( $column['capability'] ) ) {
					return false;
				}

				return is_array( $column['preset'] ) ? in_array( $id, $column['preset'] ) : $column['preset'] === $id;
			} ) );

			if ( empty( $columns ) ) {
				continue;
			}

			$presets[] = [
				'id'      => $id,
				'name'    => $name,
				'columns' => array_values( $columns )
			];
		}

		return $presets;
	}

	/**
	 * Register a new contact table column
	 *
	 * @param string   $id         the ID of the info card
	 * @param string   $title      the title of the info card
	 * @param callable $callback   callback to display the data
	 * @param string   $orderby    if the column will be sortable
	 * @param int      $priority   how high in the cards it should be displayed
	 * @param string   $capability the minimum capability for the viewing user to see the data in this card.
	 * @param string   $preset     the preset this column is added to.
	 */
	public static function register( string $id, string $title, callable $callback, $orderby = false, $priority = 100, $capability = 'view_contacts', $preset = '' ) {

		if ( empty( $id ) || ! is_callable( $callback ) ) {
			return;
		}

		self::$columns[ sanitize_key( $id ) ] = [
			'id'         => sanitize_key( $id ),
			'title'      => $title,
			'callback'   => $callback,
			'orderby'    => $orderby,
			'priority'   => $priority,
			'capability' => $capability,
			'preset'     => $preset,
		];

		// If order by is defined, make sure that it's a registered key for the contact query
		if ( $orderby ) {
			add_filter( 'groundhogg/contact_query/allowed_orderby_keys', function ( $keys ) use ( $orderby ) {
				$keys[] = $orderby;

				return $keys;
			} );
		}
	}

	/**
	 * Output the column
	 *
	 * @param $contact   Contact
	 * @param $column_id string
	 */
	public static function column_callback( Contact $contact, string $column_id ) {

		$column = get_array_var( self::$columns, $column_id );

		if ( ! $column || ! current_user_can( $column['capability'] ) ) {
			return;
		}

		$result = call_user_func( $column['callback'], $contact, $column_id, $column );

		if ( $result ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $result;
		}
	}

	/**
	 * Register the core cards
	 */
	public function register_core_columns() {

		self::register_preset( 'defaults', esc_html__( 'Defaults', 'groundhogg' ) );
		self::register_preset( 'minimal', esc_html__( 'Minimal', 'groundhogg' ) );

		self::register( 'status', esc_html__( 'Status', 'groundhogg' ), [
			self::class,
			'column_optin_status'
		], 'optin_status', 1, 'view_contacts', [ 'defaults', 'minimal' ] );

		// Core columns
		self::register( 'first_name', esc_html__( 'First Name', 'groundhogg' ), [
			self::class,
			'column_first_name'
		], 'first_name', 2, 'view_contacts', [ 'defaults', 'minimal' ] );
		self::register( 'last_name', esc_html__( 'Last Name', 'groundhogg' ), [
			self::class,
			'column_last_name'
		], 'last_name', 3, 'view_contacts', [ 'defaults', 'minimal' ] );
		self::register( 'user_id', esc_html__( 'Username', 'groundhogg' ), [ self::class, 'column_user_id' ], 'user_id', 10, 'list_users', 'defaults' );
		self::register( 'owner_id', esc_html__( 'Owner', 'groundhogg' ), [ self::class, 'column_owner_id' ], 'owner_id', 10, 'view_contacts', 'defaults' );
		self::register( 'tel_numbers', esc_html__( 'Phone', 'groundhogg' ), [ self::class, 'column_tel_numbers' ], false, 10, 'view_contacts', 'defaults' );
		self::register( 'date_created', esc_html__( 'Date Created', 'groundhogg' ), [
			self::class,
			'column_date_created'
		], 'date_created', 10, 'view_contacts', 'defaults' );

		// Other Columns
		self::register( 'tags_col', esc_html__( 'Tags' , 'groundhogg' ), [ self::class, 'column_tags' ], 'tc.tag_count', 11, 'view_contacts', [ 'defaults', 'minimal' ] );
		self::register( 'address', esc_html__( 'Location' , 'groundhogg' ), [ self::class, 'column_location' ], false, 11, 'view_contacts', 'defaults' );
		self::register( 'birthday', esc_html__( 'Birthday' , 'groundhogg' ), [ self::class, 'column_birthday' ], 'cm.birthday', 11, 'view_contacts', 'defaults' );

		do_action( 'groundhogg/admin/contacts/register_table_columns', $this );

		self::register_preset( 'unsub', esc_html__( 'Unsubscribe', 'groundhogg' ) );

		self::register( 'date_unsubscribed', esc_html__( 'Unsubscribed', 'groundhogg' ), function ( Contact $contact ) {
			if ( ! $contact->optin_status_is( Preferences::UNSUBSCRIBED ) ) {
				return '';
			}

			$activity = new Activity( [
				'activity_type' => Activity::UNSUBSCRIBED,
				'contact_id'    => $contact->ID
			] );

			if ( ! $activity->exists() ) {
				$date = new DateTimeHelper( $contact->date_optin_status_changed );
			} else {
				$date = new DateTimeHelper( $activity->get_timestamp() );
			}

			return $date->wpDateTimeFormat();
		}, 'date_optin_status_changed', 90, 'view_contacts', 'unsub' );

		self::register( 'unsub_reason', esc_html__( 'Unsub Reason', 'groundhogg' ), function ( Contact $contact ) {
			if ( ! $contact->optin_status_is( Preferences::UNSUBSCRIBED ) ) {
				return '';
			}

			$activity = new Activity( [
				'activity_type' => Activity::UNSUBSCRIBED,
				'contact_id'    => $contact->ID
			] );

			if ( ! $activity->exists() ) {
				return '';
			}

			return esc_html( get_array_var( get_unsub_reasons(), $activity->get_meta( 'reason' ) ) );
		}, false, 91, 'view_contacts', 'unsub' );

		self::register( 'unsub_feedback', esc_html__( 'Unsub Feedback', 'groundhogg' ), function ( Contact $contact ) {
			if ( ! $contact->optin_status_is( Preferences::UNSUBSCRIBED ) ) {
				return '';
			}

			$activity = new Activity( [
				'activity_type' => Activity::UNSUBSCRIBED,
				'contact_id'    => $contact->ID
			] );

			if ( ! $activity->exists() ) {
				return '';
			}

			return esc_html( $activity->get_meta( 'feedback' ) );
		}, false, 92, 'view_contacts', 'unsub' );
	}

	# =============== COLUMN CALLBACKS FOR CORE COLUMNS =============== #

	/**
	 * @param $contact Contact
	 *
	 * @return void
	 */
	protected static function column_optin_status( $contact ) {
		?>
        <span class="pill sm gh-has-tooltip <?php echo $contact->is_marketable() ? 'green marketable' : 'red unmarketable' ?>"><?php echo esc_html( Preferences::get_preference_pretty_name( $contact->get_optin_status() ) ) ?><span
                    class="gh-tooltip right"><?php echo esc_html( Plugin::instance()->preferences->get_optin_status_text( $contact ) ) ?></span></span>
		<?php
	}

	/**
	 * @param $contact Contact
	 *
	 * @return void
	 */
	protected static function column_first_name( $contact ) {
		echo $contact->get_first_name() ? esc_html( $contact->get_first_name() ) : '&#x2014;';
	}

	/**
	 * @param $contact Contact
	 *
	 * @return void
	 */
	protected static function column_last_name( $contact ) {
		echo $contact->get_last_name() ? esc_html( $contact->get_last_name() ) : '&#x2014;';
	}

	/**
	 * @param $contact Contact
	 *
	 * @return void
	 */
	protected static function column_user_id( $contact ) {
		echo $contact->get_userdata() ? '<a href="' . esc_url( admin_url( 'user-edit.php?user_id=' . $contact->get_userdata()->ID ) ). '">' . esc_html( $contact->get_userdata()->display_name ) . '</a>' : '&#x2014;';
	}

	/**
	 * @param $contact Contact
	 *
	 * @return void
	 */
	protected static function column_owner_id( $contact ) {
		echo $contact->owner_id ? '<a href="' . esc_url( admin_page_url( 'gh_contacts', [ 'owner' => $contact->owner_id ] ) ) . '">' . esc_html( $contact->get_ownerdata()->display_name ) . '</a>' : '&#x2014;';
	}

	/**
	 * @param $contact Contact
	 *
	 * @return void
	 */
	protected static function column_date_created( $contact ) {
		$dc_time = mysql2date( 'U', $contact->get_date_created() );
		$dc_time = Plugin::instance()->utils->date_time->convert_to_utc_0( $dc_time );

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo scheduled_time_column( $dc_time, false, false, false );
	}

	/**
	 * Display tags
	 *
	 * @param $contact Contact
	 */
	protected static function column_tags( Contact $contact ) {

		?>
        <div class="gh-tags" style="gap: 5px" title="<?php esc_attr_e( 'Tags', 'groundhogg' ); ?>">
			<?php
			$tags = $contact->get_tags();

			foreach ( array_splice( $tags, 0, 10 ) as $tag ):
				$tag = new Tag( $tag ) ?><a
                class="gh-tag" href="<?php echo esc_url( admin_page_url( 'gh_contacts', [ 'tags_include' => $tag->tag_id ] ) ) ?>"><?php echo esc_html( $tag->get_name() ); ?></a><?php endforeach; ?>
			<?php if ( count( $tags ) > 0 ): ?>
				<?php
                /* translators: 1: number of additional tags */
                printf( esc_html__( 'and %s more...', 'groundhogg' ), count( $tags ) );
                ?>
			<?php endif; ?>
        </div>
		<?php
	}

	/**
	 * Display tags
	 *
	 * @param $contact Contact
	 */
	protected static function column_birthday( Contact $contact ) {

		if ( ! $contact->birthday ) {
			return;
		}

		$date = date_i18n( get_option( 'date_format' ), strtotime( $contact->birthday ) );
		$age  = $contact->get_age();

		/* translators: 1: formatted birth date, 2: age in years */
        printf( esc_html__( 'Born %1$s | %2$s years old', 'groundhogg' ), "<abbr title=\"" . esc_attr( __( 'Date of birth', 'groundhogg' ) ) . "\">" . esc_html( $date ) . "</abbr>", esc_html( $age ) );
	}

	/**
	 * Display tags
	 *
	 * @param $contact Contact
	 */
	protected static function column_tel_numbers( Contact $contact ) {
		if ( $contact->get_phone_number() ): ?>
            <div class="phone"
                 title="<?php esc_attr_e( 'Primary phone number', 'groundhogg' ); ?>"><?php
                dashicon_e( 'phone' );
                html( 'a', [ 'href' => 'tel:' . $contact->get_phone_number() ], esc_html( $contact->get_phone_number() ) );
                if ( $contact->get_phone_extension() ): ?>
					<span class="extension"><?php
                        /* translators: 1: phone extension number */
                        echo esc_html( sprintf( __( 'ext. %s', 'groundhogg' ), $contact->get_phone_extension() ) )
                        ?></span>
				<?php endif; ?>
            </div>
		<?php endif;
		if ( $contact->get_mobile_number() ): ?>
            <div class="phone"
                 title="<?php esc_attr_e( 'Mobile phone number', 'groundhogg' ); ?>"><?php
                dashicon_e( 'smartphone' );
                html( 'a', [ 'href' => 'tel:' . $contact->get_mobile_number() ], esc_html( $contact->get_mobile_number() ) )
                ?>
            </div>
		<?php endif;
	}

	/**
	 * Display tags
	 *
	 * @param $contact Contact
	 */
	protected static function column_location( Contact $contact ) {

		if ( count( $contact->get_address() ) > 0 ):
			?>
            <div class="address">
				<?php html( 'a', [
					'href'   => 'https://www.google.com/maps/place/' . implode( ',+', $contact->get_address() ),
					'target' => '_blank'
				], esc_html( implode( ', ', $contact->get_address() ) ) ) ?>
            </div>
		<?php
		endif;
		?><span class="sub"><?php

		if ( $contact->get_ip_address() ) {
			?>
            <span class="ip-address"><?php echo esc_html( $contact->get_ip_address() ); ?></span>
			<?php
		}

		if ( $contact->get_time_zone() ) {

			if ( $contact->get_ip_address() ) {
				echo " | ";
			}

			?>
            <span class="time-zone">
                <?php echo esc_html( $contact->get_time_zone() ); ?> (<?php
                /* translators: 1: UTC sign (+/-), 2: UTC offset hours */
                printf( esc_html__( 'UTC %1$s%2$s', 'groundhogg' ), intval( $contact->get_time_zone_offset() ) < 0 ? '-' : '+', absint( $contact->get_time_zone_offset() / HOUR_IN_SECONDS ) )
                ?>)
            </span>
			<?php
		}
		?></span><?php
	}

}
