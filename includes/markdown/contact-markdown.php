<?php

namespace Groundhogg\Markdown;

use Groundhogg\Classes\Activity;
use Groundhogg\Classes\Message;
use Groundhogg\Broadcast;
use Groundhogg\Contact;
use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Email;
use Groundhogg\Event;
use Groundhogg\Funnel;
use Groundhogg\Pii;
use Groundhogg\Preferences;
use Groundhogg\Properties;
use Groundhogg\Step;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_db;
use function Groundhogg\is_a_contact;
use function Groundhogg\utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A contact's sections, see Markdown. Each lists the newest first, up to the limit.
 *
 *     Contact_Markdown::render( $contact, [ 'details', 'tags', 'activity' ] );
 */
class Contact_Markdown {

	/**
	 * A contact as markdown, see Markdown::render()
	 *
	 * @param Contact|int $contact
	 * @param string[]    $keys the sections, all of them when empty
	 * @param array       $args
	 *
	 * @return string
	 */
	public static function render( $contact, array $keys = [], array $args = [] ): string {

		$contact = is_a_contact( $contact ) ? $contact : get_contactdata( $contact );

		return is_a_contact( $contact ) ? Markdown::render( 'contact', $contact, $keys, $args ) : '';
	}

	/**
	 * The built in sections
	 *
	 * @return void
	 */
	public static function register_sections() {

		$sections = [
			'details'     => [
				__( 'Details', 'groundhogg' ),
				__( 'Their opt-in status, owner, when they were added, where they came from, location, language, company and job title.', 'groundhogg' ),
			],
			'personal'    => [
				__( 'Personal details', 'groundhogg' ),
				__( 'Their name, email address, phone numbers, address and birthday.', 'groundhogg' ),
				true,
			],
			'tags'        => [
				__( 'Tags', 'groundhogg' ),
				__( 'Every tag they have.', 'groundhogg' ),
			],
			'fields'      => [
				__( 'Custom fields', 'groundhogg' ),
				__( 'The custom fields that have a value. Fields marked as personal are left out unless personal details are sent.', 'groundhogg' ),
			],
			'notes'       => [
				__( 'Notes', 'groundhogg' ),
				__( 'The notes on their record, newest first.', 'groundhogg' ),
			],
			'tasks'       => [
				__( 'Tasks', 'groundhogg' ),
				__( 'Tasks about them, and whether they\'re done.', 'groundhogg' ),
			],
			'messages'    => [
				__( 'Messages', 'groundhogg' ),
				__( 'The emails and replies between them and your team, newest first.', 'groundhogg' ),
			],
			'activity'    => [
				__( 'Activity', 'groundhogg' ),
				__( 'What they did: emails opened, links clicked, logins, unsubscribes and more, newest first.', 'groundhogg' ),
			],
			'emails'      => [
				__( 'Emails', 'groundhogg' ),
				__( 'The emails and broadcasts sent to them, and whether they opened them or clicked.', 'groundhogg' ),
			],
			'flows'       => [
				__( 'Flows', 'groundhogg' ),
				__( 'The flows they\'ve been through, how far they got, and what\'s waiting for them.', 'groundhogg' ),
			],
			'submissions' => [
				__( 'Forms', 'groundhogg' ),
				__( 'The forms they filled in, and imports they came from.', 'groundhogg' ),
			],
			'page_visits' => [
				__( 'Page visits', 'groundhogg' ),
				__( 'The pages on the site they visited, and how often.', 'groundhogg' ),
			],
		];

		$options = self::section_options();

		foreach ( $sections as $key => $section ) {
			Markdown::register( 'contact', $key, [
				'label'       => $section[0],
				'description' => $section[1],
				'personal'    => $section[2] ?? false,
				'callback'    => [ __CLASS__, $key ],
				'options'     => $options[ $key ] ?? [],
			] );
		}
	}

	/**
	 * What each section can be narrowed to, see Markdown::list_options(). Everything when left out.
	 *
	 * @return array[] by section
	 */
	protected static function section_options(): array {

		$list = Markdown::list_options();

		$ids = function ( string $label, string $description, string $type ) {
			return [
				'type'        => 'ids',
				'label'       => $label,
				'description' => $description,
				'name'        => function ( $id ) use ( $type ) {
					return self::title( $type, absint( $id ) );
				},
			];
		};

		$flows      = $ids( __( 'Flows', 'groundhogg' ), __( 'Only these flows, by ID.', 'groundhogg' ), 'funnel' );
		$broadcasts = $ids( __( 'Broadcasts', 'groundhogg' ), __( 'Only these broadcasts, by ID.', 'groundhogg' ), 'broadcast' );
		$emails     = $ids( __( 'Emails', 'groundhogg' ), __( 'Only these emails, by ID, wherever they were sent from.', 'groundhogg' ), 'email' );

		$fields = [];

		foreach ( Properties::instance()->get_fields() as $field ) {
			if ( ! empty( $field['name'] ) ) {
				$fields[ $field['name'] ] = $field['label'] ?? $field['name'];
			}
		}

		return [
			'fields'      => [
				'fields' => [
					'type'        => 'strings',
					'label'       => __( 'Fields', 'groundhogg' ),
					'description' => __( 'Only these custom fields, by name.', 'groundhogg' ),
					'choices'     => $fields,
				],
			],
			'notes'       => $list,
			'tasks'       => $list,
			'messages'    => $list + [
					'direction' => [
						'type'        => 'strings',
						'label'       => __( 'Direction', 'groundhogg' ),
						'description' => __( 'Only the ones they wrote, or only the ones sent to them.', 'groundhogg' ),
						'choices'     => [
							Message::INBOUND  => __( 'They wrote', 'groundhogg' ),
							Message::OUTBOUND => __( 'Sent to them', 'groundhogg' ),
						],
					],
				],
			'activity'    => $list + [
					'types'         => [
						'type'        => 'strings',
						'label'       => __( 'Kinds', 'groundhogg' ),
						'description' => __( 'Only these kinds of activity.', 'groundhogg' ),
						'choices'     => self::activity_types(),
					],
					'funnel_ids'    => $flows,
					'broadcast_ids' => $broadcasts,
					'email_ids'     => $emails,
				],
			'emails'      => $list + [
					'funnel_ids'    => $flows,
					'broadcast_ids' => $broadcasts,
					'email_ids'     => $emails,
				],
			'flows'       => $list + [
					'funnel_ids' => $flows,
				],
			'submissions' => $list + [
					'form_ids' => $ids( __( 'Forms', 'groundhogg' ), __( 'Only these forms, by the ID of their step in a flow.', 'groundhogg' ), 'step' ),
				],
			'page_visits' => $list,
		];
	}

	/**
	 * The kinds of activity to pick from, by what's in the activity table
	 *
	 * @return string[] type => label
	 */
	public static function activity_types(): array {

		/**
		 * Filter the kinds of activity a contact's activity can be narrowed to, type => label. Add an add-on's own.
		 *
		 * @param string[] $types
		 */
		return apply_filters( 'groundhogg/markdown/contact/activity_types', [
			Activity::EMAIL_OPENED    => __( 'Opened an email', 'groundhogg' ),
			Activity::EMAIL_CLICKED   => __( 'Clicked a link in an email', 'groundhogg' ),
			Activity::SMS_CLICKED     => __( 'Clicked a link in a text message', 'groundhogg' ),
			Activity::FORM_SUBMISSION => __( 'Filled in a form', 'groundhogg' ),
			Activity::UNSUBSCRIBED    => __( 'Unsubscribed', 'groundhogg' ),
			Activity::PAGE_VIEW       => __( 'Viewed a page', 'groundhogg' ),
			Activity::LOGIN           => __( 'Logged in', 'groundhogg' ),
			Activity::LOGOUT          => __( 'Logged out', 'groundhogg' ),
			Activity::BOUNCE          => __( 'Bounced', 'groundhogg' ),
			Activity::SOFT_BOUNCE     => __( 'Soft bounced', 'groundhogg' ),
			Activity::COMPLAINT       => __( 'Marked as spam', 'groundhogg' ),
			'funnel_conversion'       => __( 'Reached a flow\'s goal', 'groundhogg' ),
			'clicked_superlink'       => __( 'Clicked a superlink', 'groundhogg' ),
			'imported'                => __( 'Was imported', 'groundhogg' ),
		] );
	}

	/**
	 * Keep a query to the flows, broadcasts and emails in $args: what's from any of the flows or broadcasts, and from
	 * one of the emails
	 *
	 * @param \Groundhogg\DB\Query\Where $where
	 * @param array                      $args
	 * @param string                     $broadcast_column where a broadcast's ID is, step_id in the activity and events
	 *
	 * @return void
	 */
	protected static function where_sent_from( $where, array $args, string $broadcast_column = 'step_id' ) {

		$funnels    = $args['funnel_ids'] ?? [];
		$broadcasts = $args['broadcast_ids'] ?? [];

		if ( $funnels || $broadcasts ) {
			$where->subOr( function ( $or ) use ( $funnels, $broadcasts, $broadcast_column ) {

				if ( $funnels ) {
					$or->in( 'funnel_id', $funnels );
				}

				// a broadcast's activity has the broadcasts' own funnel ID, and the broadcast's ID as its step
				if ( $broadcasts ) {
					$or->subAnd( function ( $and ) use ( $broadcasts, $broadcast_column ) {
						$and->equals( 'funnel_id', Broadcast::FUNNEL_ID )->in( $broadcast_column, $broadcasts );
					} );
				}
			} );
		}

		if ( ! empty( $args['email_ids'] ) ) {
			$where->in( 'email_id', $args['email_ids'] );
		}
	}

	/**
	 * Free text about the contact, like a note or a deal's description, made one line, without their own details
	 * unless personal details are being sent. A section with text someone wrote should put it through here.
	 *
	 * @param mixed   $text
	 * @param Contact $contact
	 * @param array   $args the section's $args
	 * @param int     $max  characters
	 *
	 * @return string
	 */
	public static function free_text( $text, Contact $contact, array $args, int $max = 500 ): string {

		$text = Markdown::text( $text, $max );

		return $args['personal'] ? $text : Pii::scrub_text( $text, $contact, true );
	}

	/**
	 * Remembered titles, so a long history doesn't load the same email or step again and again
	 *
	 * @var string[]
	 */
	protected static $titles = [];

	protected static function title( string $type, int $id ): string {

		if ( ! $id ) {
			return '';
		}

		$key = $type . ':' . $id;

		if ( isset( self::$titles[ $key ] ) ) {
			return self::$titles[ $key ];
		}

		switch ( $type ) {
			case 'email':
				$email = new Email( $id );
				$title = $email->exists() ? ( $email->get_subject_line() ?: $email->get_title() ) : '';
				break;
			case 'funnel':
				$funnel = new Funnel( $id );
				$title  = $funnel->exists() ? $funnel->get_title() : '';
				break;
			case 'step':
				$step  = new Step( $id );
				$title = $step->exists() ? $step->get_title() : '';
				break;
			case 'broadcast':
				$broadcast = new Broadcast( $id );
				$title     = $broadcast->exists() ? $broadcast->get_title() : '';
				break;
			default:
				$title = '';
		}

		return self::$titles[ $key ] = Markdown::text( $title, 150 );
	}

	/**
	 * Quote a title the way the lists do, or say there isn't one
	 *
	 * @param string $title
	 *
	 * @return string
	 */
	protected static function quote( string $title ): string {
		return $title !== '' ? '"' . $title . '"' : __( '(deleted)', 'groundhogg' );
	}

	/* ---- the sections ----------------------------------------------------------------------------------------- */

	public static function details( Contact $contact, array $args ): string {

		$owner   = $contact->get_ownerdata();
		$user    = $contact->get_userdata();
		$country = $contact->get_meta( 'country' );

		$pairs = [
			__( 'Opt-in status', 'groundhogg' )        => Preferences::get_preference_pretty_name( $contact->get_optin_status() ),
			__( 'Can receive marketing', 'groundhogg' ) => $contact->is_marketable() ? __( 'Yes', 'groundhogg' ) : __( 'No', 'groundhogg' ),
			__( 'Contact since', 'groundhogg' )        => Markdown::date( $contact->get_date_created() ),
			__( 'Owner', 'groundhogg' )                => $owner ? $owner->display_name : '',
			__( 'Lead source', 'groundhogg' )          => Markdown::text( $contact->get_meta( 'lead_source' ), 200 ),
			__( 'Signed up on', 'groundhogg' )         => Markdown::text( $contact->get_meta( 'source_page' ), 200 ),
			__( 'Country', 'groundhogg' )              => $country ? utils()->location->get_countries_list( $country ) : '',
			__( 'Region', 'groundhogg' )               => Markdown::text( $contact->get_meta( 'region' ), 100 ),
			__( 'Time zone', 'groundhogg' )            => $contact->get_time_zone(),
			__( 'Language', 'groundhogg' )             => $contact->get_locale(),
			__( 'Company', 'groundhogg' )              => Markdown::text( $contact->get_company(), 150 ),
			__( 'Job title', 'groundhogg' )            => Markdown::text( $contact->get_job_title(), 150 ),
			__( 'Website account', 'groundhogg' )      => $user ? sprintf(
			/* translators: %s: the user's roles */
				__( 'Yes, %s', 'groundhogg' ),
				implode( ', ', array_map( 'translate_user_role', array_map( function ( $role ) {
					return wp_roles()->roles[ $role ]['name'] ?? $role;
				}, $user->roles ) ) )
			) : __( 'No', 'groundhogg' ),
		];

		/**
		 * Filter what the details section says about a contact, label => value. Add-ons add what they know, like a
		 * lead score.
		 *
		 * @param array   $pairs
		 * @param Contact $contact
		 * @param array   $args
		 */
		$pairs = apply_filters( 'groundhogg/markdown/contact/details', $pairs, $contact, $args );

		return Markdown::pairs( $pairs );
	}

	public static function personal( Contact $contact, array $args ): string {

		$address = array_filter( [
			$contact->get_meta( 'street_address_1' ),
			$contact->get_meta( 'street_address_2' ),
			$contact->get_meta( 'city' ),
			$contact->get_meta( 'region' ),
			$contact->get_meta( 'postal_zip' ),
		] );

		return Markdown::pairs( [
			__( 'Name', 'groundhogg' )          => $contact->get_full_name(),
			__( 'Email address', 'groundhogg' ) => $contact->get_email(),
			__( 'Phone', 'groundhogg' )         => $contact->get_phone_number(),
			__( 'Mobile', 'groundhogg' )        => $contact->get_mobile_number(),
			__( 'Address', 'groundhogg' )       => implode( ', ', $address ),
			__( 'Birthday', 'groundhogg' )      => $contact->get_meta( 'birthday' ),
		] );
	}

	public static function tags( Contact $contact, array $args ): string {

		$names = array_map( function ( $tag ) {
			return $tag->get_name();
		}, array_values( $contact->get_tags( true ) ) );

		sort( $names, SORT_NATURAL | SORT_FLAG_CASE );

		return implode( ', ', $names );
	}

	public static function fields( Contact $contact, array $args ): string {

		$pairs = [];
		$only  = $args['fields'] ?? [];

		foreach ( Properties::instance()->get_fields() as $field ) {

			if ( empty( $field['name'] ) || ( ! empty( $field['pii'] ) && ! $args['personal'] ) ) {
				continue;
			}

			if ( $only && ! in_array( $field['name'], $only, true ) ) {
				continue;
			}

			$value = $contact->get_meta( $field['name'] );

			if ( is_array( $value ) ) {
				$value = implode( ', ', array_filter( array_map( 'strval', $value ), 'strlen' ) );
			}

			$label           = Markdown::text( $field['label'] ?? $field['name'], 100 );
			$pairs[ $label ] = self::free_text( $value, $contact, $args, 300 );
		}

		return Markdown::pairs( $pairs );
	}

	public static function notes( Contact $contact, array $args ): string {

		$query = new Table_Query( 'notes' );
		$query->where()->equals( 'object_type', 'contact' )->equals( 'object_id', $contact->get_id() );
		Markdown::where_in_range( $query->where(), 'timestamp', $args );
		$query->setOrderby( 'timestamp' )->setOrder( 'DESC' )->setLimit( $args['limit'] );

		$items = [];

		foreach ( $query->get_results() as $note ) {

			$by   = $note->context === 'system' ? __( 'the system', 'groundhogg' ) : self::user_name( $note->user_id );
			$text = trim( self::free_text( $note->summary, $contact, $args, 150 ) . ' ' . self::free_text( $note->content, $contact, $args ) );

			if ( $text !== '' ) {
				/* translators: 1: date, 2: kind of note, like note or call, 3: who added it, 4: the note */
				$items[] = sprintf( __( '%1$s, %2$s by %3$s: %4$s', 'groundhogg' ), Markdown::date( $note->timestamp ), $note->type ?: 'note', $by, $text );
			}
		}

		return Markdown::items( $items );
	}

	public static function tasks( Contact $contact, array $args ): string {

		$query = new Table_Query( 'tasks' );
		$query->where()->equals( 'object_type', 'contact' )->equals( 'object_id', $contact->get_id() );
		Markdown::where_in_range( $query->where(), 'due_date', $args, 'mysql' );
		$query->setOrderby( 'due_date' )->setOrder( 'DESC' )->setLimit( $args['limit'] );

		$items = [];

		foreach ( $query->get_results() as $task ) {

			$text = trim( self::free_text( $task->summary, $contact, $args, 150 ) . ' ' . self::free_text( $task->content, $contact, $args, 300 ) );
			$done = $task->date_completed && strpos( $task->date_completed, '0000' ) !== 0;

			$items[] = sprintf(
			/* translators: 1: the task, 2: when it's due, 3: done, or not done yet */
				__( '%1$s (due %2$s, %3$s)', 'groundhogg' ),
				$text ?: __( 'A task', 'groundhogg' ),
				Markdown::date( $task->due_date ),
				$done ? __( 'done', 'groundhogg' ) : ( strtotime( $task->due_date . ' UTC' ) < time() ? __( 'overdue', 'groundhogg' ) : __( 'not done yet', 'groundhogg' ) )
			);
		}

		return Markdown::items( $items );
	}

	public static function messages( Contact $contact, array $args ): string {

		$query = new Table_Query( 'messages' );
		$query->where()->equals( 'object_type', 'contact' )->equals( 'object_id', $contact->get_id() );
		Markdown::where_in_range( $query->where(), 'date_created', $args, 'mysql' );

		if ( ! empty( $args['direction'] ) ) {
			$query->where()->in( 'direction', $args['direction'] );
		}

		$query->setOrderby( 'date_created' )->setOrder( 'DESC' )->setLimit( $args['limit'] );

		// a few messages are sent whole, like their latest reply for a step to classify. Many are cut short.
		$few   = $args['limit'] <= 3;
		$items = [];

		foreach ( $query->get_results() as $message ) {

			$text = $few
				? self::free_text( $message->content ?: $message->summary, $contact, $args, 3000 )
				: self::free_text( $message->summary ?: $message->content, $contact, $args, 400 );

			$items[] = sprintf(
			/* translators: 1: date, 2: they wrote, or sent to them, 3: the subject, 4: what it says */
				__( '%1$s, %2$s %3$s: %4$s', 'groundhogg' ),
				Markdown::date( $message->date_created ),
				$message->direction === Message::INBOUND ? __( 'they wrote', 'groundhogg' ) : __( 'sent to them', 'groundhogg' ),
				self::quote( self::free_text( $message->subject, $contact, $args, 150 ) ),
				$text
			);
		}

		return Markdown::items( $items );
	}

	public static function activity( Contact $contact, array $args ): string {

		$query = new Table_Query( 'activity' );
		$query->where()
		      ->equals( 'contact_id', $contact->get_id() )
		      // seeing a form isn't something they did
		      ->notEquals( 'activity_type', 'form_impression' );

		Markdown::where_in_range( $query->where(), 'timestamp', $args );
		self::where_sent_from( $query->where(), $args );

		if ( ! empty( $args['types'] ) ) {
			$query->where()->in( 'activity_type', $args['types'] );
		}

		$query->setOrderby( 'timestamp' )->setOrder( 'DESC' )->setLimit( $args['limit'] );

		$items = [];

		foreach ( $query->get_results() as $row ) {

			$line = Markdown::date( $row->timestamp ) . ': ' . self::activity_line( $row );

			/**
			 * Filter how a contact's activity is written, like for an add-on's own activity type
			 *
			 * @param string  $line
			 * @param object  $row the activity, from the activity table
			 * @param Contact $contact
			 */
			$items[] = apply_filters( 'groundhogg/markdown/contact/activity', $line, $row, $contact );
		}

		return Markdown::items( $items );
	}

	/**
	 * What a row of the activity table says they did
	 *
	 * @param object $row
	 *
	 * @return string
	 */
	protected static function activity_line( $row ): string {

		$email = self::quote( self::title( 'email', absint( $row->email_id ) ) );
		$link  = Markdown::text( $row->referer, 200 );

		switch ( $row->activity_type ) {
			case Activity::EMAIL_OPENED:
				/* translators: %s: the email's subject */
				return sprintf( __( 'Opened the email %s', 'groundhogg' ), $email );
			case Activity::EMAIL_CLICKED:
				/* translators: 1: the link, 2: the email's subject */
				return sprintf( __( 'Clicked %1$s in the email %2$s', 'groundhogg' ), $link, $email );
			case Activity::SMS_CLICKED:
				/* translators: %s: the link */
				return sprintf( __( 'Clicked %s in a text message', 'groundhogg' ), $link );
			case Activity::FORM_SUBMISSION:
				/* translators: %s: the form */
				return sprintf( __( 'Filled in the form %s', 'groundhogg' ), self::quote( self::title( 'step', absint( $row->step_id ) ) ) );
			case Activity::UNSUBSCRIBED:
				return __( 'Unsubscribed', 'groundhogg' );
			case Activity::PAGE_VIEW:
				/* translators: %s: the page */
				return sprintf( __( 'Viewed %s', 'groundhogg' ), $link );
			case Activity::LOGIN:
				return __( 'Logged in to the website', 'groundhogg' );
			case Activity::LOGOUT:
				return __( 'Logged out of the website', 'groundhogg' );
			case Activity::BOUNCE:
				return __( 'An email to them bounced, the address doesn\'t work', 'groundhogg' );
			case Activity::SOFT_BOUNCE:
				return __( 'An email to them bounced for now, like a full inbox', 'groundhogg' );
			case Activity::COMPLAINT:
				return __( 'Marked an email as spam', 'groundhogg' );
			case 'funnel_conversion':
				/* translators: %s: the flow */
				return sprintf( __( 'Reached the goal of the flow %s', 'groundhogg' ), self::quote( self::title( 'funnel', absint( $row->funnel_id ) ) ) );
			case 'imported':
				return __( 'Was imported', 'groundhogg' );
			case 'clicked_superlink':
				/* translators: %s: the link */
				return $link ? sprintf( __( 'Clicked the superlink %s', 'groundhogg' ), $link ) : __( 'Clicked a superlink', 'groundhogg' );
		}

		// an add-on's own, like a purchase
		$line = ucfirst( str_replace( [ '_', '-' ], ' ', sanitize_key( $row->activity_type ) ) );

		if ( $link ) {
			$line .= ': ' . $link;
		}

		if ( (float) $row->value ) {
			/* translators: %s: an amount, like an order total */
			$line .= ' ' . sprintf( __( '(value %s)', 'groundhogg' ), number_format_i18n( (float) $row->value, 2 ) );
		}

		return $line;
	}

	public static function emails( Contact $contact, array $args ): string {

		$query = new Table_Query( 'events' );
		$query->where()
		      ->equals( 'contact_id', $contact->get_id() )
		      ->greaterThan( 'email_id', 0 )
		      ->equals( 'status', Event::COMPLETE );

		Markdown::where_in_range( $query->where(), 'time', $args );
		self::where_sent_from( $query->where(), $args );

		$query->setOrderby( 'time' )->setOrder( 'DESC' )->setLimit( $args['limit'] );

		$events = $query->get_results();

		if ( empty( $events ) ) {
			return '';
		}

		// what they did with each, in one go
		$done     = [];
		$activity = new Table_Query( 'activity' );
		$activity->setSelect( 'event_id', 'activity_type' );
		$activity->where()
		         ->equals( 'contact_id', $contact->get_id() )
		         ->in( 'event_id', wp_list_pluck( $events, 'ID' ) );

		foreach ( $activity->get_results() as $row ) {
			$done[ $row->event_id ][ $row->activity_type ] = true;
		}

		$items = [];

		foreach ( $events as $event ) {

			$did = $done[ $event->ID ] ?? [];

			if ( ! empty( $did[ Activity::EMAIL_CLICKED ] ) ) {
				$what = __( 'opened and clicked', 'groundhogg' );
			} else if ( ! empty( $did[ Activity::EMAIL_OPENED ] ) ) {
				$what = __( 'opened', 'groundhogg' );
			} else {
				$what = __( 'not opened', 'groundhogg' );
			}

			$items[] = sprintf(
			/* translators: 1: date, 2: broadcast or flow email, 3: the email's subject, 4: whether it was opened */
				__( '%1$s: %2$s %3$s, %4$s', 'groundhogg' ),
				Markdown::date( $event->time ),
				absint( $event->event_type ) === Event::BROADCAST ? __( 'Broadcast', 'groundhogg' ) : __( 'Email', 'groundhogg' ),
				self::quote( self::title( 'email', absint( $event->email_id ) ) ),
				$what
			);
		}

		return Markdown::items( $items );
	}

	public static function flows( Contact $contact, array $args ): string {

		global $wpdb;

		$events = get_db( 'events' )->table_name;
		$queue  = get_db( 'event_queue' )->table_name;

		// only the flows asked for, the IDs are integers by now
		$only = empty( $args['funnel_ids'] ) ? '' : ' AND funnel_id IN (' . implode( ',', array_map( 'absint', $args['funnel_ids'] ) ) . ')';

		// the steps they went through in the time range
		$range = '';

		if ( ! empty( $args['since'] ) ) {
			$range .= $wpdb->prepare( ' AND time >= %d', $args['since'] );
		}

		if ( ! empty( $args['until'] ) ) {
			$range .= $wpdb->prepare( ' AND time <= %d', $args['until'] );
		}

		// each flow, the newest first: how many steps they got through and the last one
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- the tables are ours, the values are prepared or integers
		$flows = $wpdb->get_results( $wpdb->prepare( "SELECT funnel_id, COUNT(*) AS steps, MAX(time) AS last_time, SUBSTRING_INDEX(GROUP_CONCAT(step_id ORDER BY time DESC), ',', 1) AS last_step FROM {$events} WHERE contact_id = %d AND event_type = %d AND status = %s{$only}{$range} GROUP BY funnel_id ORDER BY last_time DESC LIMIT %d", $contact->get_id(), Event::FUNNEL, Event::COMPLETE, $args['limit'] ) );

		// what's waiting for them
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- the tables are ours, the values are prepared or integers
		$waiting = $wpdb->get_results( $wpdb->prepare( "SELECT funnel_id, step_id, time FROM {$queue} WHERE contact_id = %d AND event_type = %d AND status = %s{$only} ORDER BY time ASC", $contact->get_id(), Event::FUNNEL, Event::WAITING ) );

		$next = [];

		foreach ( $waiting as $event ) {
			if ( ! isset( $next[ $event->funnel_id ] ) ) {
				$next[ $event->funnel_id ] = $event;
			}
		}

		$items = [];

		foreach ( $flows as $flow ) {

			$line = sprintf(
			/* translators: 1: the flow, 2: number of steps, 3: the last step, 4: when */
				_n( '%1$s: went through %2$s step, the last was %3$s on %4$s', '%1$s: went through %2$s steps, the last was %3$s on %4$s', $flow->steps, 'groundhogg' ),
				self::quote( self::title( 'funnel', absint( $flow->funnel_id ) ) ),
				number_format_i18n( $flow->steps ),
				self::quote( self::title( 'step', absint( $flow->last_step ) ) ),
				Markdown::date( $flow->last_time )
			);

			if ( isset( $next[ $flow->funnel_id ] ) ) {
				$line .= '. ' . self::waiting_line( $next[ $flow->funnel_id ] );
				unset( $next[ $flow->funnel_id ] );
			}

			$items[] = $line;
		}

		// flows they're waiting in without having finished a step yet
		foreach ( $next as $event ) {
			$items[] = self::quote( self::title( 'funnel', absint( $event->funnel_id ) ) ) . ': ' . self::waiting_line( $event );
		}

		return Markdown::items( $items );
	}

	protected static function waiting_line( $event ): string {
		/* translators: 1: a step, 2: when */
		return sprintf( __( 'Next is %1$s on %2$s', 'groundhogg' ), self::quote( self::title( 'step', absint( $event->step_id ) ) ), Markdown::date( $event->time ) );
	}

	public static function submissions( Contact $contact, array $args ): string {

		$query = new Table_Query( 'submissions' );
		$query->where()->equals( 'contact_id', $contact->get_id() );
		// stored in the site's time zone, unlike most dates
		Markdown::where_in_range( $query->where(), 'date_created', $args, 'local' );

		if ( ! empty( $args['form_ids'] ) ) {
			$query->where()->in( 'step_id', $args['form_ids'] );
		}

		$query->setOrderby( 'date_created' )->setOrder( 'DESC' )->setLimit( $args['limit'] );

		$items = [];

		foreach ( $query->get_results() as $submission ) {

			switch ( $submission->type ) {
				case 'form':
					/* translators: %s: the form */
					$what = sprintf( __( 'Filled in the form %s', 'groundhogg' ), self::quote( self::title( 'step', absint( $submission->step_id ) ) ) );
					break;
				case 'import':
					$what = __( 'Was imported from a file', 'groundhogg' );
					break;
				default:
					$what = ucfirst( str_replace( [ '_', '-' ], ' ', sanitize_key( $submission->type ) ) );
			}

			$items[] = Markdown::date( $submission->date_created, true ) . ': ' . $what;
		}

		return Markdown::items( $items );
	}

	public static function page_visits( Contact $contact, array $args ): string {

		$query = new Table_Query( 'page_visits' );
		$query->where()->equals( 'contact_id', $contact->get_id() );
		Markdown::where_in_range( $query->where(), 'timestamp', $args );
		$query->setOrderby( 'timestamp' )->setOrder( 'DESC' )->setLimit( $args['limit'] );

		$items = [];

		foreach ( $query->get_results() as $visit ) {
			$items[] = sprintf(
			/* translators: 1: the page, 2: number of views, 3: when they last visited */
				_n( '%1$s (%2$s view, last on %3$s)', '%1$s (%2$s views, last on %3$s)', absint( $visit->views ), 'groundhogg' ),
				Markdown::text( $visit->path, 200 ),
				number_format_i18n( absint( $visit->views ) ),
				Markdown::date( $visit->timestamp )
			);
		}

		return Markdown::items( $items );
	}

	/**
	 * Who did something, by their display name
	 *
	 * @param int $user_id
	 *
	 * @return string
	 */
	protected static function user_name( $user_id ): string {
		$user = get_userdata( absint( $user_id ) );

		return $user ? $user->display_name : __( 'someone', 'groundhogg' );
	}
}
