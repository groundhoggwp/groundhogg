<?php

namespace Groundhogg\Markdown;

use Groundhogg\Contact;
use Groundhogg\Main_Roles;
use function Groundhogg\get_db;
use function Groundhogg\get_team_ids;
use function Groundhogg\has_team;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A contact's sections for what the official add-ons and the stores they connect to know about them: companies,
 * deals, lead score, appointments, and WooCommerce and Easy Digital Downloads orders. Each is only there when what it
 * reads from is running, so an assistant or the contact summary gets them without the add-on doing anything.
 *
 * An add-on is running when its tables are registered, which only happens once it has loaded with everything it
 * needs, see Extension::init(). Its version constant is defined either way.
 *
 * Reads what the add-ons store, the way they read it themselves, and only for someone who could see it there: each
 * section checks the add-on's own capability, like view_deals, or the store's, like manage_woocommerce. In a flow
 * nobody is signed in, and everything is there. If an add-on adds its own sections, it should use the same key, see
 * Markdown::register(), which replaces the one here.
 */
class Add_On_Markdown {

	/**
	 * Register the sections of the add-ons that are running
	 *
	 * @return void
	 */
	public static function register_sections() {

		if ( get_db( 'companies' ) && class_exists( '\GroundhoggCompanies\Classes\Company' ) ) {
			Markdown::register( 'contact', 'companies', [
				'label'       => __( 'Companies', 'groundhogg' ),
				'description' => __( 'The companies they work for: name, website, industry and what the company does.', 'groundhogg' ),
				'callback'    => [ __CLASS__, 'companies' ],
				'can'         => function () {
					return self::can( 'view_companies' );
				},
			] );
		}

		if ( get_db( 'deals' ) && class_exists( '\GroundhoggPipeline\Classes\Deal' ) ) {
			Markdown::register( 'contact', 'deals', [
				'label'       => __( 'Deals', 'groundhogg' ),
				'description' => __( 'Their deals: pipeline, stage, value, whether they were won or lost, and when they\'re expected to close.', 'groundhogg' ),
				'callback'    => [ __CLASS__, 'deals' ],
				'can'         => [ __CLASS__, 'can_see_deals' ],
			] );
		}

		if ( get_db( 'appointments' ) && class_exists( '\GroundhoggBookingCalendar\Classes\Calendar' ) ) {
			Markdown::register( 'contact', 'appointments', [
				'label'       => __( 'Appointments', 'groundhogg' ),
				'description' => __( 'The appointments they booked, past and upcoming, and whether they were cancelled.', 'groundhogg' ),
				'callback'    => [ __CLASS__, 'appointments' ],
				'can'         => [ __CLASS__, 'can_see_appointments' ],
			] );
		}

		if ( function_exists( 'wc_get_orders' ) ) {
			Markdown::register( 'contact', 'woocommerce', [
				'label'       => __( 'WooCommerce orders', 'groundhogg' ),
				'description' => __( 'What they bought in the WooCommerce store, when, for how much, and the order\'s status.', 'groundhogg' ),
				'callback'    => [ __CLASS__, 'woocommerce' ],
				// the same as the WooCommerce card on their record
				'can'         => function () {
					return self::can( 'manage_woocommerce' );
				},
			] );
		}

		if ( function_exists( 'edd_get_orders' ) && function_exists( 'edd_get_customer_by' ) ) {
			Markdown::register( 'contact', 'edd', [
				'label'       => __( 'Easy Digital Downloads orders', 'groundhogg' ),
				'description' => __( 'What they bought in the Easy Digital Downloads store, when, for how much, and the order\'s status.', 'groundhogg' ),
				'callback'    => [ __CLASS__, 'edd' ],
				// what Easy Digital Downloads needs to see orders
				'can'         => function () {
					return self::can( 'edit_shop_payments' );
				},
			] );
		}

		// a line in their details
		if ( get_db( 'ls_levels' ) && class_exists( '\GroundhoggLeadScoring\Classes\Level' ) && ! has_filter( 'groundhogg/markdown/contact/details', [ __CLASS__, 'lead_score' ] ) ) {
			add_filter( 'groundhogg/markdown/contact/details', [ __CLASS__, 'lead_score' ], 10, 2 );
		}
	}

	/* ---- the sections ----------------------------------------------------------------------------------------- */

	/**
	 * @param Contact $contact
	 * @param array   $args
	 *
	 * @return string
	 */
	public static function companies( Contact $contact, array $args ): string {

		if ( ! self::can( 'view_companies' ) ) {
			return '';
		}

		$items = [];

		foreach ( array_slice( array_filter( $contact->get_related_objects( 'company', false ) ), 0, $args['limit'] ) as $company ) {

			/** @var \GroundhoggCompanies\Classes\Company $company */
			$parts = array_filter( [
				Markdown::text( $company->get_domain(), 150 ),
				Markdown::text( $company->get_meta( 'industry' ), 100 ),
				Contact_Markdown::free_text( $company->get_description(), $contact, $args, 300 ),
			] );

			$items[] = '"' . Markdown::text( $company->get_name(), 150 ) . '"' . ( $parts ? ': ' . implode( ', ', $parts ) : '' );
		}

		return Markdown::items( $items );
	}

	/**
	 * The deals the current person may see, the way the Pipeline add-on decides: a sales rep their own, a manager with a
	 * team their team's. Loading a contact's deals goes around the add-on's own check.
	 *
	 * @param Contact $contact
	 * @param array   $args
	 *
	 * @return string
	 */
	public static function deals( Contact $contact, array $args ): string {

		if ( ! self::can( 'view_deals' ) ) {
			return '';
		}

		$owners = self::deal_owners();

		$deals = array_filter( $contact->get_related_objects( 'deal', false ), function ( $deal ) use ( $owners ) {
			return $deal && ( $owners === null || in_array( absint( $deal->get_owner_id() ), $owners, true ) );
		} );

		$items = [];

		foreach ( array_slice( $deals, 0, $args['limit'] ) as $deal ) {

			/** @var \GroundhoggPipeline\Classes\Deal $deal */
			$pipeline = $deal->get_pipeline();
			$stage    = $deal->get_stage();

			if ( $deal->is_won() ) {
				$status = __( 'won', 'groundhogg' );
			} else if ( $deal->is_lost() ) {
				$status = __( 'lost', 'groundhogg' );
			} else {
				$status = __( 'open', 'groundhogg' );
			}

			$value = function_exists( '\GroundhoggPipeline\format_price' )
				? wp_strip_all_tags( html_entity_decode( \GroundhoggPipeline\format_price( $deal->get_deal_value() ) ) )
				: number_format_i18n( (float) $deal->get_deal_value(), 2 );

			$details = [
				/* translators: 1: the pipeline, 2: the stage */
				$pipeline && $stage ? sprintf( __( 'in %1$s at %2$s', 'groundhogg' ), self::quote( $pipeline->get_title() ), self::quote( $stage->get_title() ) ) : '',
				$status,
				$value,
			];

			if ( ! $deal->is_closed() ) {
				$details[] = sprintf(
				/* translators: %s: a percentage */
					__( '%s%% likely to close', 'groundhogg' ),
					number_format_i18n( round( (float) $deal->get_close_probability() * 100 ) )
				);

				$expected = (string) $deal->projected_close_date;

				if ( $expected && strpos( $expected, '0000' ) !== 0 ) {
					/* translators: %s: a date */
					$details[] = sprintf( __( 'expected to close %s', 'groundhogg' ), $expected );
				}
			} else if ( $deal->date_closed && strpos( (string) $deal->date_closed, '0000' ) !== 0 ) {
				/* translators: %s: a date */
				$details[] = sprintf( __( 'closed %s', 'groundhogg' ), substr( (string) $deal->date_closed, 0, 10 ) );
			}

			$products = array_filter( array_map( function ( $product ) {
				return is_array( $product ) ? Markdown::text( $product['name'] ?? '', 80 ) : '';
			}, (array) $deal->get_meta( 'product_interest' ) ) );

			if ( $products ) {
				/* translators: %s: products */
				$details[] = sprintf( __( 'products: %s', 'groundhogg' ), implode( ', ', $products ) );
			}

			$items[] = self::quote( $deal->get_title() ) . ' ' . implode( ', ', array_filter( $details ) );
		}

		return Markdown::items( $items );
	}

	/**
	 * Their score and level, in their details. Read from what the add-on stored, since asking it works the score out
	 * again and changes their tags.
	 *
	 * @param array   $pairs
	 * @param Contact $contact
	 *
	 * @return array
	 */
	public static function lead_score( $pairs, $contact ) {

		$points = $contact->get_meta( 'lead_score_points' );

		if ( $points === '' || $points === false || $points === null ) {
			return $pairs;
		}

		$level = new \GroundhoggLeadScoring\Classes\Level( absint( $contact->get_meta( 'lead_score_level_id' ) ) );

		$pairs[ __( 'Lead score', 'groundhogg' ) ] = $level->exists()
			/* translators: 1: points, 2: the level, like Hot */
			? sprintf( __( '%1$s points, %2$s', 'groundhogg' ), number_format_i18n( (int) $points ), Markdown::text( $level->get_name(), 60 ) )
			/* translators: %s: points */
			: sprintf( __( '%s points', 'groundhogg' ), number_format_i18n( (int) $points ) );

		return $pairs;
	}

	/**
	 * Through the add-on's own query, which keeps a rep to their own appointments
	 *
	 * @param Contact $contact
	 * @param array   $args
	 *
	 * @return string
	 */
	public static function appointments( Contact $contact, array $args ): string {

		// the add-on's query keeps someone to their own appointments, but only when they can see appointments at all
		if ( ! self::can( 'view_appointments' ) ) {
			return '';
		}

		$rows = get_db( 'appointments' )->query( [
			'contact_id' => $contact->get_id(),
			'orderby'    => 'start_time',
			'order'      => 'DESC',
			'limit'      => $args['limit'],
		] );

		$items     = [];
		$calendars = [];

		foreach ( (array) $rows as $row ) {

			$calendar_id = absint( $row->calendar_id ?? 0 );

			if ( ! isset( $calendars[ $calendar_id ] ) ) {
				$calendar                  = new \GroundhoggBookingCalendar\Classes\Calendar( $calendar_id );
				$calendars[ $calendar_id ] = $calendar->exists() ? Markdown::text( $calendar->get_name(), 100 ) : '';
			}

			$start = absint( $row->start_time );

			if ( $row->status === 'cancelled' ) {
				$when = __( 'cancelled', 'groundhogg' );
			} else {
				$when = $start > time() ? __( 'upcoming', 'groundhogg' ) : __( 'happened', 'groundhogg' );
			}

			$owner = ! empty( $row->owner_id ) ? get_userdata( absint( $row->owner_id ) ) : false;

			$items[] = sprintf(
			/* translators: 1: date, 2: the calendar, 3: upcoming, happened or cancelled */
				__( '%1$s: %2$s, %3$s', 'groundhogg' ),
				Markdown::date( $start ),
				$calendars[ $calendar_id ] ? self::quote( $calendars[ $calendar_id ] ) : __( 'an appointment', 'groundhogg' ),
				$when
			) . ( $owner ? ' ' . sprintf(
				/* translators: %s: who it's with */
					__( 'with %s', 'groundhogg' ),
					$owner->display_name
				) : '' );
		}

		return Markdown::items( $items );
	}

	/**
	 * Their orders the way the WooCommerce add-on finds them: by their website account, or by their email address
	 *
	 * @param Contact $contact
	 * @param array   $args
	 *
	 * @return string
	 */
	public static function woocommerce( Contact $contact, array $args ): string {

		// the same as the WooCommerce card on their record
		if ( ! self::can( 'manage_woocommerce' ) ) {
			return '';
		}

		$user  = $contact->get_userdata();
		$query = [
			'limit'   => $args['limit'],
			'orderby' => 'date',
			'order'   => 'DESC',
		];

		if ( $user ) {
			$query['customer_id'] = $user->ID;
		} else if ( $contact->get_email() ) {
			$query['customer'] = $contact->get_email();
		} else {
			return '';
		}

		$items = [];

		if ( $user && function_exists( 'wc_get_customer_total_spent' ) ) {
			$items[] = sprintf(
			/* translators: 1: number of orders, 2: amount */
				__( 'In total: %1$s orders, %2$s spent', 'groundhogg' ),
				number_format_i18n( wc_get_customer_order_count( $user->ID ) ),
				self::money( wc_price( wc_get_customer_total_spent( $user->ID ) ) )
			);
		}

		foreach ( wc_get_orders( $query ) as $order ) {

			if ( ! $order instanceof \WC_Order ) {
				continue;
			}

			$date     = $order->get_date_paid() ?: $order->get_date_created();
			$products = [];

			foreach ( $order->get_items() as $item ) {
				$products[] = Markdown::text( $item->get_name(), 80 );
			}

			$items[] = sprintf(
			/* translators: 1: date, 2: products, 3: total, 4: status */
				__( '%1$s: %2$s, %3$s, %4$s', 'groundhogg' ),
				$date ? Markdown::date( $date->getTimestamp() ) : '',
				implode( ', ', array_filter( $products ) ) ?: __( 'an order', 'groundhogg' ),
				self::money( $order->get_formatted_order_total() ),
				strtolower( wc_get_order_status_name( $order->get_status() ) )
			);
		}

		return Markdown::items( $items );
	}

	/**
	 * Their orders the way the Easy Digital Downloads add-on finds them: the customer with their email address
	 *
	 * @param Contact $contact
	 * @param array   $args
	 *
	 * @return string
	 */
	public static function edd( Contact $contact, array $args ): string {

		// what Easy Digital Downloads needs to see orders
		if ( ! self::can( 'edit_shop_payments' ) ) {
			return '';
		}

		$customer = $contact->get_email() ? edd_get_customer_by( 'email', $contact->get_email() ) : false;

		if ( ! $customer && $contact->get_user_id() ) {
			$customer = edd_get_customer_by( 'user_id', $contact->get_user_id() );
		}

		if ( ! $customer ) {
			return '';
		}

		$money = function ( $amount ) {
			return function_exists( 'edd_currency_filter' ) && function_exists( 'edd_format_amount' )
				? self::money( edd_currency_filter( edd_format_amount( $amount ) ) )
				: number_format_i18n( (float) $amount, 2 );
		};

		$items = [
			sprintf(
			/* translators: 1: number of orders, 2: amount */
				__( 'In total: %1$s orders, %2$s spent', 'groundhogg' ),
				number_format_i18n( absint( $customer->purchase_count ) ),
				$money( $customer->purchase_value )
			),
		];

		$orders = edd_get_orders( [
			'customer_id' => $customer->id,
			'type'        => 'sale',
			'number'      => $args['limit'],
			'orderby'     => 'date_created',
			'order'       => 'DESC',
		] );

		foreach ( (array) $orders as $order ) {

			$products = [];

			foreach ( (array) $order->get_items() as $item ) {
				$products[] = Markdown::text( $item->product_name, 80 );
			}

			$items[] = sprintf(
			/* translators: 1: date, 2: products, 3: total, 4: status */
				__( '%1$s: %2$s, %3$s, %4$s', 'groundhogg' ),
				Markdown::date( $order->date_completed ?: $order->date_created ),
				implode( ', ', array_filter( $products ) ) ?: __( 'an order', 'groundhogg' ),
				$money( $order->total ),
				strtolower( function_exists( 'edd_get_payment_status_label' ) ? edd_get_payment_status_label( $order->status ) : $order->status )
			);
		}

		return Markdown::items( $items );
	}

	/* ---- who may see what ------------------------------------------------------------------------------------- */

	/**
	 * The owners whose deals the current person may see, null for everyone's, the way the Pipeline add-on decides
	 *
	 * @return int[]|null
	 */
	protected static function deal_owners() {

		$user_id = get_current_user_id();

		if ( $user_id && has_team() ) {
			return array_map( 'absint', get_team_ids() );
		}

		if ( $user_id && Main_Roles::is_sales_representative() ) {
			return [ $user_id ];
		}

		return null;
	}

	/**
	 * @return bool|string
	 */
	public static function can_see_deals() {

		if ( ! self::can( 'view_deals' ) ) {
			return false;
		}

		return self::deal_owners() === null ? true : __( 'Only the deals of the person this is for, or their team, are shown. There may be others.', 'groundhogg' );
	}

	/**
	 * @return bool|string
	 */
	public static function can_see_appointments() {

		if ( ! self::can( 'view_appointments' ) ) {
			return false;
		}

		return self::can( 'view_others_appointments' ) ? true : __( 'Only their appointments with the person this is for are shown. There may be others.', 'groundhogg' );
	}

	/* ---- helpers ---------------------------------------------------------------------------------------------- */

	/**
	 * Whether the current person may see what a section shows. In a flow nobody is signed in, and everything is there.
	 *
	 * @param string $cap
	 *
	 * @return bool
	 */
	protected static function can( string $cap ): bool {
		return ! get_current_user_id() || current_user_can( $cap );
	}

	/**
	 * A price the store formatted as HTML, as text
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	protected static function money( $html ): string {
		return trim( html_entity_decode( wp_strip_all_tags( (string) $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	}

	protected static function quote( $title ): string {
		$title = Markdown::text( $title, 150 );

		return $title !== '' ? '"' . $title . '"' : __( '(untitled)', 'groundhogg' );
	}
}
