<?php

namespace Groundhogg\Reporting;

use Groundhogg\Classes\Message;
use Groundhogg\Classes\Task;
use Groundhogg\Contact;
use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Main_Roles;
use Groundhogg\Notifications\Signed_Link;
use Groundhogg\Notifications\Subscriptions;
use Groundhogg\Pii;
use Groundhogg\Templates\Notifications\Notification_Builder;
use Groundhogg\Utils\DateTimeHelper;
use Groundhogg\Utils\Limits;
use Groundhogg\Utils\Replacer;
use function Groundhogg\_nf;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_array_var;
use function Groundhogg\get_db;
use function Groundhogg\get_team_ids;
use function Groundhogg\html;
use function Groundhogg\run_as_user;
use function Groundhogg\white_labeled_name;
use function Groundhogg\Ymd_His;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The My Day email: what a user has on today, sent at the time they chose, in their timezone.
 *
 * It's a list of sections, each of them a card with a title. A section only shows when it has something to say, and when
 * none of them have, nothing is sent. Core has tasks, replies and new contacts, and an add-on adds its own (deals,
 * appointments) with the `groundhogg/email_reports/my_day_sections` filter.
 *
 * A user who manages a team can have the same sections for their team as well, in a Team group after their own.
 */
class My_Day extends Notification_Builder {

	/**
	 * The user meta
	 */
	const META_ENABLED   = 'gh_my_day';
	const META_TEAM      = 'gh_my_day_team';
	const META_HOUR      = 'gh_my_day_hour';
	const META_TIMEZONE  = 'gh_my_day_timezone';
	const META_LAST_SENT = 'gh_my_day_last_sent';

	/**
	 * The hour of the day, in the user's timezone, that it's sent when they haven't chosen
	 */
	const DEFAULT_HOUR = 8;

	/**
	 * It is sent in the hours after the chosen one, and not after that. A site that was down or not running its schedule
	 * for most of the day shouldn't send "your day" at night.
	 */
	const WINDOW_HOURS = 6;

	/**
	 * The most that a section is asked to show
	 */
	const LIMIT = 10;

	public function __construct() {
		add_action( 'groundhogg/cleanup', [ __CLASS__, 'dispatch' ] );
	}

	/**
	 * ---------------------------------------------------------------------------------------------------------------
	 * What a user chose
	 * ---------------------------------------------------------------------------------------------------------------
	 */

	/**
	 * @param int $user_id
	 *
	 * @return int the hour, 0 to 23
	 */
	public static function get_hour( int $user_id ): int {

		$hour = get_user_meta( $user_id, self::META_HOUR, true );

		if ( $hour === '' || $hour === false ) {
			return self::DEFAULT_HOUR;
		}

		return min( 23, max( 0, absint( $hour ) ) );
	}

	/**
	 * @param int $user_id
	 *
	 * @return \DateTimeZone the one the user chose, or the site's
	 */
	public static function get_timezone( int $user_id ): \DateTimeZone {

		$timezone = get_user_meta( $user_id, self::META_TIMEZONE, true );

		if ( $timezone && in_array( $timezone, timezone_identifiers_list(), true ) ) {
			return new \DateTimeZone( $timezone );
		}

		return wp_timezone();
	}

	/**
	 * Whether the user gets the email at all
	 *
	 * @param \WP_User|int $user
	 *
	 * @return bool
	 */
	public static function is_subscribed( $user ): bool {

		$user = $user instanceof \WP_User ? $user : get_userdata( $user );

		return $user && user_can( $user, 'view_contacts' ) && Subscriptions::is_subscribed( $user->ID, 'my_day' );
	}

	/**
	 * Whether the email has the team's sections in it
	 *
	 * @param \WP_User $user
	 *
	 * @return bool
	 */
	public static function wants_team( \WP_User $user ): bool {
		return (bool) get_user_meta( $user->ID, self::META_TEAM, true ) && Main_Roles::is_sales_manager( $user );
	}

	/**
	 * Save what the user chose on their profile
	 *
	 * @param int   $user_id
	 * @param array $data    enabled, team, hour and timezone
	 *
	 * @return void
	 */
	public static function save_preferences( int $user_id, array $data ) {

		$user = get_userdata( $user_id );

		if ( ! $user || ! user_can( $user, 'view_contacts' ) ) {
			delete_user_meta( $user_id, self::META_ENABLED );
			delete_user_meta( $user_id, self::META_TEAM );

			return;
		}

		$was_subscribed = self::is_subscribed( $user );

		update_user_meta( $user_id, self::META_ENABLED, ! empty( $data['enabled'] ) ? 1 : 0 );
		update_user_meta( $user_id, self::META_TEAM, ! empty( $data['team'] ) && Main_Roles::is_sales_manager( $user ) ? 1 : 0 );

		if ( isset( $data['hour'] ) && $data['hour'] !== '' ) {
			update_user_meta( $user_id, self::META_HOUR, min( 23, max( 0, absint( $data['hour'] ) ) ) );
		}

		$timezone = isset( $data['timezone'] ) ? sanitize_text_field( $data['timezone'] ) : '';

		if ( $timezone && in_array( $timezone, timezone_identifiers_list(), true ) ) {
			update_user_meta( $user_id, self::META_TIMEZONE, $timezone );
		} else {
			delete_user_meta( $user_id, self::META_TIMEZONE );
		}

		// Someone who turns it on after their time today gets it tomorrow, not the moment the schedule next runs
		if ( ! $was_subscribed && self::is_subscribed( $user ) && time() >= self::send_time_today( $user_id ) ) {
			self::mark_sent( $user_id );
		}
	}

	/**
	 * ---------------------------------------------------------------------------------------------------------------
	 * When it's sent
	 * ---------------------------------------------------------------------------------------------------------------
	 */

	/**
	 * @param int $user_id
	 * @param int $now
	 *
	 * @return DateTimeHelper the user's date today, in their timezone
	 */
	protected static function local_now( int $user_id, int $now = 0 ): DateTimeHelper {
		return new DateTimeHelper( $now ?: time(), self::get_timezone( $user_id ) );
	}

	/**
	 * @param int $user_id
	 * @param int $now
	 *
	 * @return int the timestamp of the user's chosen time today, in their timezone
	 */
	public static function send_time_today( int $user_id, int $now = 0 ): int {

		$time = self::local_now( $user_id, $now );
		$time->setTime( self::get_hour( $user_id ), 0, 0 );

		return $time->getTimestamp();
	}

	/**
	 * Whether it's time to send the user's email: it's past their time, not too far past it, and it hasn't gone today
	 *
	 * @param int $user_id
	 * @param int $now
	 *
	 * @return bool
	 */
	public static function is_due( int $user_id, int $now = 0 ): bool {

		$now    = $now ?: time();
		$target = self::send_time_today( $user_id, $now );

		if ( $now < $target || $now >= $target + self::WINDOW_HOURS * HOUR_IN_SECONDS ) {
			return false;
		}

		return get_user_meta( $user_id, self::META_LAST_SENT, true ) !== self::local_now( $user_id, $now )->ymd();
	}

	/**
	 * Say that today's has gone, or been given up on, so that it isn't queued again
	 *
	 * @param int $user_id
	 * @param int $now
	 *
	 * @return void
	 */
	public static function mark_sent( int $user_id, int $now = 0 ) {
		update_user_meta( $user_id, self::META_LAST_SENT, self::local_now( $user_id, $now )->ymd() );
	}

	/**
	 * Send the email of every user it's time for. Runs every hour.
	 *
	 * It's done here and not as a background task per user, because that would leave a finished row in the background
	 * tasks table for every user, every day. Anyone it doesn't get to before the time or memory limits are reached isn't
	 * marked as sent, so the next run picks them up, until their window closes.
	 *
	 * @param int $now
	 *
	 * @return int how many were handled
	 */
	public static function dispatch( $now = 0 ): int {

		$now = $now ? absint( $now ) : time();

		$user_ids = get_users( [
			'fields'     => 'ID',
			'meta_query' => [
				'relation' => 'OR',
				[ 'key' => self::META_ENABLED, 'value' => '1' ],
				[ 'key' => self::META_TEAM, 'value' => '1' ],
			],
		] );

		$handled = 0;

		Limits::start();

		foreach ( $user_ids as $user_id ) {

			$user_id = (int) $user_id;

			if ( ! self::is_subscribed( $user_id ) || ! self::is_due( $user_id, $now ) ) {
				continue;
			}

			if ( Limits::limits_exceeded() ) {
				break;
			}

			// first, so that a failure can't make it go again and again, or a second run overlapping this one send it twice
			self::mark_sent( $user_id, $now );

			try {
				self::send_to_user( $user_id );
			} catch ( \Throwable $e ) {
				do_action( 'groundhogg/my_day/failed', $user_id, $e );
			}

			Limits::processed_action();

			$handled ++;
		}

		Limits::stop();

		return $handled;
	}

	/**
	 * Build and send the email of a user, if there's anything in it
	 *
	 * @param int $user_id
	 *
	 * @return bool whether it was sent
	 */
	public static function send_to_user( int $user_id ): bool {

		$user = get_userdata( $user_id );

		if ( ! $user || ! self::is_subscribed( $user ) ) {
			return false;
		}

		$email = self::build( $user );

		if ( ! $email ) {
			return false;
		}

		return Email_Reports::mail_report( 'my-day', [ $user->user_email ], $email['subject'], $email['html'] );
	}

	/**
	 * ---------------------------------------------------------------------------------------------------------------
	 * What's in it
	 * ---------------------------------------------------------------------------------------------------------------
	 */

	/**
	 * The sections, in the order they show
	 *
	 * @return array[] id => [ id, title, link, priority, cap, team_cap, scopes, render ]
	 */
	public static function get_sections(): array {

		$sections = [
			'tasks'    => [
				'title'    => __( 'Tasks due', 'groundhogg' ),
				'link'     => admin_page_url( 'groundhogg' ),
				'priority' => 10,
				'cap'      => 'view_tasks',
				'team_cap' => 'view_others_tasks',
				'render'   => [ __CLASS__, 'render_tasks' ],
			],
			'replies'  => [
				'title'    => __( 'Replies waiting', 'groundhogg' ),
				'link'     => admin_page_url( 'gh_contacts' ),
				'priority' => 20,
				'cap'      => 'view_contacts',
				'team_cap' => 'view_others_contacts',
				'render'   => [ __CLASS__, 'render_replies' ],
			],
			'contacts' => [
				'title'    => __( 'New contacts', 'groundhogg' ),
				'link'     => admin_page_url( 'gh_contacts' ),
				'priority' => 30,
				'cap'      => 'view_contacts',
				'team_cap' => 'view_others_contacts',
				'render'   => [ __CLASS__, 'render_contacts' ],
			],
		];

		/**
		 * The sections of the My Day email. A section is a card in the email. Add one by returning it with an ID that is
		 * yours, and nothing is shown when its render callback returns an empty string.
		 *
		 * - title    string   the card's heading
		 * - link     string   where the card's "Open" link goes
		 * - priority int      lower shows first, 10 per core section
		 * - cap      string   what a user needs to have the section, checked as that user
		 * - team_cap string   what a user needs to have the team's version of the section
		 * - scopes   string[] 'me' and 'team', which of them the section has, both by default
		 * - render   callable ( \WP_User $user, string $scope, array $context ): string, the card's content as HTML, with
		 *                      scope as 'me' or 'team'. It's called while the current user is the user it's for, so what
		 *                      they aren't allowed to see (a contact's personal information) is already left out of
		 *                      anything built from the objects. The context has since, until (timestamps), end_of_day (in
		 *                      the site's timezone), timezone and limit.
		 *
		 * @param array[] $sections
		 */
		$sections = apply_filters( 'groundhogg/email_reports/my_day_sections', $sections );

		$normalized = [];

		foreach ( $sections as $id => $section ) {

			if ( ! is_array( $section ) || ! isset( $section['render'] ) || ! is_callable( $section['render'] ) ) {
				continue;
			}

			$normalized[ $id ] = wp_parse_args( $section, [
				'id'       => $id,
				'title'    => '',
				'link'     => admin_page_url( 'groundhogg' ),
				'priority' => 10,
				'cap'      => '',
				'team_cap' => '',
				'scopes'   => [ 'me', 'team' ],
			] );
		}

		uasort( $normalized, function ( $a, $b ) {
			return $a['priority'] <=> $b['priority'];
		} );

		return $normalized;
	}

	/**
	 * Build the email of a user
	 *
	 * @param \WP_User $user
	 * @param int      $now
	 *
	 * @return array|false subject and html, or false when none of the sections has anything to say
	 */
	public static function build( \WP_User $user, int $now = 0 ) {

		return run_as_user( $user->ID, function () use ( $user, $now ) {

			$now      = $now ?: time();
			$timezone = self::get_timezone( $user->ID );

			$context = [
				'since'      => $now - DAY_IN_SECONDS,
				'until'      => $now,
				'timezone'   => $timezone,
				// due dates are in the site's time, the end of the user's day in it
				'end_of_day' => ( new DateTimeHelper( 'today 23:59:59', $timezone ) )->setTimezone( wp_timezone() )->ymdhis(),
				'limit'      => self::LIMIT,
			];

			$groups = [];

			if ( Subscriptions::is_subscribed( $user->ID, 'my_day' ) && get_user_meta( $user->ID, self::META_ENABLED, true ) ) {
				$groups[''] = self::render_sections( $user, 'me', $context );
			}

			if ( self::wants_team( $user ) ) {
				$groups[ __( 'Your team', 'groundhogg' ) ] = self::render_sections( $user, 'team', $context );
			}

			$groups = array_filter( $groups );

			if ( empty( $groups ) ) {
				return false;
			}

			$sections_html = '';

			foreach ( $groups as $heading => $cards ) {

				// a heading is only needed to tell one group from another
				if ( $heading !== '' || count( $groups ) > 1 ) {
					$sections_html .= self::heading( $heading !== '' ? $heading : __( 'You', 'groundhogg' ) );
				}

				$sections_html .= implode( '', $cards );
			}

			$greeting = sprintf(
			/* translators: %s: the first name, or if there isn't one the display name of the user */
				esc_html__( 'Here is what is on for today, %s.', 'groundhogg' ),
				esc_html( $user->first_name ?: $user->display_name )
			);

			$html = self::get_general_notification_template_html( 'my-day', [
				'unsubscribe_url' => Email_Reports::UNSUBSCRIBE_PLACEHOLDER,
				'dashboard_url'   => admin_page_url( 'groundhogg' ),
			] );

			// Last, and in one pass, so that what's in a task's summary is never taken for something to replace
			$html = strtr( $html, [
				'<!--gh-my-day-greeting-->' => $greeting,
				'<!--gh-my-day-sections-->' => $sections_html,
			] );

			$subject = sprintf(
			/* translators: 1: the plugin name, 2: the date, e.g. Mon, Nov 2 */
				__( '[%1$s] Your day: %2$s', 'groundhogg' ),
				white_labeled_name(),
				wp_date( 'D, M j', $now, $timezone )
			);

			return [
				'subject' => $subject,
				'html'    => $html,
			];
		} );
	}

	/**
	 * The cards of the sections that have something to show, for one scope
	 *
	 * @param \WP_User $user
	 * @param string   $scope me or team
	 * @param array    $context
	 *
	 * @return string[]
	 */
	protected static function render_sections( \WP_User $user, string $scope, array $context ): array {

		$cards = [];

		foreach ( self::get_sections() as $section ) {

			if ( ! in_array( $scope, (array) $section['scopes'], true ) ) {
				continue;
			}

			if ( $section['cap'] && ! user_can( $user, $section['cap'] ) ) {
				continue;
			}

			if ( $scope === 'team' && $section['team_cap'] && ! user_can( $user, $section['team_cap'] ) ) {
				continue;
			}

			$content = trim( (string) call_user_func( $section['render'], $user, $scope, $context ) );

			if ( $content === '' ) {
				continue;
			}

			$cards[] = self::card( $section['title'], $section['link'], $content );
		}

		return $cards;
	}

	/**
	 * A card in the email
	 *
	 * @param string $title
	 * @param string $link
	 * @param string $content HTML
	 *
	 * @return string
	 */
	public static function card( string $title, string $link, string $content ): string {

		return sprintf(
			'<table cellpadding="0" cellspacing="0" width="100%%" role="presentation" style="border-collapse: separate; border: 1px solid #e7e9ec; border-radius: 5px; background-color: #ffffff; margin: 0 0 15px;">' .
			'<tr><td style="padding: 8px 12px; border-bottom: 1px solid #e7e9ec; font-family: system-ui, sans-serif; font-size: 14px;"><strong>%s</strong>&nbsp;&nbsp;&nbsp;<a href="%s">%s</a></td></tr>' .
			'<tr><td style="padding: 0;">%s</td></tr></table>',
			esc_html( $title ),
			esc_url( $link ),
			esc_html__( 'Open', 'groundhogg' ) . ' &rarr;',
			$content
		);
	}

	protected static function heading( string $text ): string {
		return sprintf(
			'<p style="font-family: system-ui, sans-serif; font-size: 13px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.04em; color: #5c6670; margin: 20px 0 8px;">%s</p>',
			esc_html( $text )
		);
	}

	/**
	 * A line of text in a card, for something that isn't a table
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	public static function note( string $html ): string {
		return '<p style="font-family: system-ui, sans-serif; font-size: 13px; margin: 0; padding: 8px 12px;">' . $html . '</p>';
	}

	/**
	 * Limit a query of anything with an owner to the user's, or their team's
	 *
	 * @param \Groundhogg\DB\Query\Where $where
	 * @param string                     $column the column that has the user's ID
	 * @param \WP_User                   $user
	 * @param string                     $scope  me or team
	 */
	protected static function scope_where( $where, string $column, \WP_User $user, string $scope ) {

		if ( $scope === 'me' ) {
			$where->equals( $column, $user->ID );

			return;
		}

		$team = self::team_ids( $user );

		// a manager with no team sees everyone's, so everyone else's is who their team is
		if ( $team === null ) {
			$where->notEquals( $column, $user->ID );

			return;
		}

		$where->in( $column, $team );
	}

	/**
	 * @param \WP_User $user
	 *
	 * @return int[]|null the team without the user, or null for a manager who has no team, which means everyone
	 */
	protected static function team_ids( \WP_User $user ) {

		$ids = array_values( array_diff( array_map( 'absint', get_team_ids( $user ) ), [ $user->ID ] ) );

		return empty( $ids ) ? null : $ids;
	}

	/**
	 * ---------------------------------------------------------------------------------------------------------------
	 * The sections of core
	 * ---------------------------------------------------------------------------------------------------------------
	 */

	/**
	 * How long until a task is due, or how long ago it was
	 *
	 * @param Task $task
	 *
	 * @return string
	 */
	public static function due_pill( Task $task ): string {

		$due = $task->get_due_date();

		if ( $task->is_overdue() ) {
			return html()->e( 'span', [
				'class' => 'pill red',
				'title' => $due->wpDateTimeFormat(),
			], sprintf( /* translators: %s: a length of time, e.g. 3 days */ __( '%s overdue', 'groundhogg' ), $due->human_time_diff() ) );
		}

		return html()->e( 'span', [
			'class' => 'pill ' . ( $task->is_due_today() ? 'orange' : '' ),
			'title' => $due->wpDateTimeFormat(),
		], sprintf( /* translators: %s: a length of time, e.g. 3 hours */ __( 'In %s', 'groundhogg' ), $due->human_time_diff() ) );
	}

	/**
	 * Tasks that are overdue or due before the end of today
	 *
	 * @param \WP_User $user
	 * @param string   $scope
	 * @param array    $context
	 *
	 * @return string
	 */
	public static function render_tasks( \WP_User $user, string $scope, array $context ): string {

		$query = new Table_Query( 'tasks' );
		$query->setOrderby( [ 'due_date', 'ASC' ] )->setLimit( $context['limit'] )->setFoundRows( true );

		$where = $query->where();
		$where->equals( 'date_completed', DateTimeHelper::ZERODATETIME )->lessThanEqualTo( 'due_date', $context['end_of_day'] );

		self::scope_where( $where, 'user_id', $user, $scope );

		$tasks = array_filter( $query->get_objects( Task::class ), function ( Task $task ) use ( $user ) {
			return user_can( $user, 'view_task', $task );
		} );

		if ( empty( $tasks ) ) {
			return '';
		}

		$more = max( 0, $query->get_found_rows() - count( $tasks ) );

		if ( $scope === 'team' ) {

			$rows = [];

			foreach ( $tasks as $task ) {

				$data = $task->get_associated_data();
				$name = esc_html( $data['name'] );
				$owner = get_userdata( $task->user_id );

				$rows[] = [
					'task'  => '<strong>' . esc_html( $task->summary ) . '</strong>' . ( $name ? '<br>' . ( $data['link'] ? '<a href="' . esc_url( $data['link'] ) . '">' . $name . '</a>' : $name ) : '' ),
					'owner' => esc_html( $owner ? $owner->display_name : '' ),
					'due'   => self::due_pill( $task ),
				];
			}

			$content = self::generate_list_table_html( [
				__( 'Task', 'groundhogg' ),
				__( 'Assigned to', 'groundhogg' ),
				__( 'Due', 'groundhogg' ),
			], $rows );

		} else {

			$template = self::get_template_part( 'single-task' );
			$content  = '';

			foreach ( $tasks as $task ) {
				$content .= self::task_row( $template, $task, $user );
			}
		}

		if ( $more > 0 ) {
			$content .= self::note( sprintf(
				/* translators: %s: a number of tasks */
				esc_html( _n( 'and %s more task', 'and %s more tasks', $more, 'groundhogg' ) ),
				_nf( $more )
			) );
		}

		return $content;
	}

	/**
	 * A task in the email, with links to complete or snooze it that work without signing in
	 *
	 * @param string   $template the HTML of single-task
	 * @param Task     $task
	 * @param \WP_User $user
	 *
	 * @return string
	 */
	protected static function task_row( string $template, Task $task, \WP_User $user ): string {

		$data = $task->get_associated_data();
		$icon = [
			'task'    => '📝',
			'call'    => '📞',
			'meeting' => '📆',
			'email'   => '✉️',
		];

		$link  = $data['link'] ?: admin_page_url( 'groundhogg' );
		$image = get_array_var( $data, 'img' );

		if ( $task->object_type === 'deal' || ! $image ) {
			$image_html = '💼';
		} else {
			$image_html = html()->e( 'img', [
				'src'    => $image,
				'width'  => 18,
				'height' => 18,
				'style'  => [ 'border-radius' => '5px', 'vertical-align' => 'text-top' ],
			] );
		}

		// a long time, it is read when it is read. Opening it doesn't do anything yet
		$ttl = 2 * WEEK_IN_SECONDS;

		// strtr and not Replacer: it works in one pass, so what's in a summary is never taken for another name to replace
		return strtr( $template, [
			'the_task_summary'       => esc_html( $task->summary ),
			'the_task_content'       => wp_kses_post( $task->content ),
			'the_task_icon'          => get_array_var( $icon, $task->type, $icon['task'] ),
			'the_object_name'        => esc_html( $data['name'] ),
			'the_object_link'        => esc_url( $link ),
			'the_object_img'         => $image_html,
			'the_task_link'          => esc_url( $link ),
			'the_task_snooze_link'   => esc_url( Signed_Link::url( 'snooze', $user->ID, (string) $task->ID, $ttl ) ),
			'the_task_complete_link' => esc_url( Signed_Link::url( 'complete', $user->ID, (string) $task->ID, $ttl ) ),
			'task_due_by'            => self::due_pill( $task ),
			'task_added_by'          => esc_html( sprintf(
				/* translators: %s: a length of time */
				__( 'Added %s ago', 'groundhogg' ),
				$task->get_date_created()->human_time_diff()
			) ),
		] );
	}

	/**
	 * Contacts with replies that haven't been read
	 *
	 * @param \WP_User $user
	 * @param string   $scope
	 * @param array    $context
	 *
	 * @return string
	 */
	public static function render_replies( \WP_User $user, string $scope, array $context ): string {

		global $wpdb;

		$messages = get_db( 'messages' )->get_table_name();
		$contacts = get_db( 'contacts' )->get_table_name();

		$join = "INNER JOIN $contacts c ON c.ID = m.object_id";
		$args = [];

		if ( $scope === 'me' ) {
			$join  .= ' AND c.owner_id = %d';
			$args[] = $user->ID;
		} else {

			$team = self::team_ids( $user );

			if ( $team === null ) {
				$join  .= ' AND c.owner_id <> %d';
				$args[] = $user->ID;
			} else {
				$join .= ' AND c.owner_id IN (' . implode( ',', array_fill( 0, count( $team ), '%d' ) ) . ')';
				$args  = array_merge( $args, $team );
			}
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names
		$found = $wpdb->get_results( $wpdb->prepare(
			"SELECT m.object_id, COUNT(*) AS unread, MAX(m.ID) AS latest_id
			FROM $messages m $join
			WHERE m.object_type = 'contact' AND m.direction = 'inbound' AND m.is_read = 0
			GROUP BY m.object_id
			ORDER BY latest_id DESC
			LIMIT %d",
			array_merge( $args, [ $context['limit'] * 2 ] ) // some won't be allowed to be seen, so there is room for them
		) );
		// phpcs:enable

		$restricted = Pii::is_restricted( $user );
		$rows       = [];

		foreach ( $found as $row ) {

			if ( count( $rows ) >= $context['limit'] ) {
				break;
			}

			$latest = new Message( (int) $row->latest_id );

			if ( ! $latest->exists() || ! user_can( $user, 'view_message', $latest ) ) {
				continue;
			}

			$contact = new Contact( (int) $row->object_id );

			if ( ! $contact->exists() ) {
				continue;
			}

			$name = Pii::contact_name( $contact );

			$cell = '<strong><a href="' . esc_url( $contact->admin_link() ) . '">' . esc_html( $name ) . '</a></strong>';

			// what it says can name the contact, and a user who can't see who they are doesn't see it
			if ( ! $restricted && $latest->subject ) {
				$cell .= '<br>' . esc_html( $latest->subject );
			}

			$cells = [
				'contact' => $cell,
				'unread'  => html()->e( 'span', [ 'class' => 'pill' ], _nf( (int) $row->unread ) ),
			];

			if ( $scope === 'team' ) {
				$owner          = get_userdata( $contact->get_owner_id() );
				$cells['owner'] = esc_html( $owner ? $owner->display_name : '' );
			}

			$rows[] = $cells;
		}

		if ( empty( $rows ) ) {
			return '';
		}

		$headers = [ __( 'Contact', 'groundhogg' ), __( 'Unread', 'groundhogg' ) ];

		if ( $scope === 'team' ) {
			$headers[] = __( 'Owner', 'groundhogg' );
		}

		return self::generate_list_table_html( $headers, $rows );
	}

	/**
	 * Contacts that came in since yesterday: the user's own, or how many each person on the team got
	 *
	 * @param \WP_User $user
	 * @param string   $scope
	 * @param array    $context
	 *
	 * @return string
	 */
	public static function render_contacts( \WP_User $user, string $scope, array $context ): string {

		// the contacts table is in the site's time
		$since = Ymd_His( $context['since'], true );

		if ( $scope === 'team' ) {

			$query = new Table_Query( 'contacts' );
			$query->setSelect( 'owner_id', [ 'count(ID)', 'total' ] )->setGroupby( 'owner_id' )->setOrderby( [ 'total', 'DESC' ] )->setLimit( $context['limit'] );

			$where = $query->where();
			$where->greaterThanEqualTo( 'date_created', $since );
			self::scope_where( $where, 'owner_id', $user, 'team' );

			$rows = [];

			foreach ( $query->get_results() as $result ) {

				$owner = get_userdata( (int) $result->owner_id );

				if ( ! $owner ) {
					continue;
				}

				$rows[] = [
					'owner' => esc_html( $owner->display_name ),
					'new'   => html()->e( 'span', [ 'class' => 'pill' ], _nf( (int) $result->total ) ),
				];
			}

			if ( empty( $rows ) ) {
				return '';
			}

			return self::generate_list_table_html( [ __( 'Owner', 'groundhogg' ), __( 'New contacts', 'groundhogg' ) ], $rows );
		}

		$query = new Table_Query( 'contacts' );
		$query->setOrderby( [ 'date_created', 'DESC' ] )->setLimit( $context['limit'] )->setFoundRows( true );
		$query->where()->equals( 'owner_id', $user->ID )->greaterThanEqualTo( 'date_created', $since );

		$found = $query->get_objects( Contact::class );

		if ( empty( $found ) ) {
			return '';
		}

		$rows = [];

		foreach ( $found as $contact ) {

			if ( ! user_can( $user, 'view_contact', $contact ) ) {
				continue;
			}

			$rows[] = [
				'contact' => '<strong><a href="' . esc_url( $contact->admin_link() ) . '">' . esc_html( Pii::contact_name( $contact ) ) . '</a></strong>',
			];
		}

		if ( empty( $rows ) ) {
			return '';
		}

		$content = self::generate_list_table_html( [ __( 'Contact', 'groundhogg' ) ], $rows );
		$more    = max( 0, $query->get_found_rows() - count( $rows ) );

		if ( $more > 0 ) {
			$content .= self::note( sprintf(
				/* translators: %s: a number of contacts */
				esc_html( _n( 'and %s more contact', 'and %s more contacts', $more, 'groundhogg' ) ),
				_nf( $more )
			) );
		}

		return $content;
	}
}
