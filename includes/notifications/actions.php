<?php

namespace Groundhogg\Notifications;

use Groundhogg\Classes\Task;
use function Groundhogg\add_managed_rewrite_rule;
use function Groundhogg\is_managed_page;
use function Groundhogg\run_as_user;
use function Groundhogg\white_labeled_name;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The page behind the signed links in notification emails (`/gh/notification/{token}/`): unsubscribe, snooze a task and
 * complete a task, for a user who isn't signed in.
 *
 * Opening a link only asks. Mail scanners and link previews open everything in an email, and a link that did something
 * when it was opened would snooze and complete tasks and unsubscribe people the moment their inbox saw the email. The
 * button on the page POSTs to the same address, and that is what does it.
 *
 * The signature is the only thing that proves who it is, so what a link can do is checked again as that user.
 */
class Actions {

	/**
	 * Bump when the rewrite rule below changes, so sites flush once without waiting for an update routine.
	 */
	const REWRITES_VERSION = 1;

	/**
	 * The days a task can be snoozed for, from the page
	 */
	const SNOOZE_OPTIONS = [ 1, 3, 7 ];

	public function __construct() {
		add_action( 'init', [ $this, 'add_rewrite_rules' ] );
		add_action( 'init', [ $this, 'maybe_flush_rewrite_rules' ], 99 );
		add_filter( 'query_vars', [ $this, 'add_query_vars' ] );

		// Before Rewrites::template_redirect() and the template loader, which don't know this sub page
		add_action( 'template_redirect', [ $this, 'template_redirect' ], 8 );
	}

	public function add_rewrite_rules() {
		add_managed_rewrite_rule( Signed_Link::ROUTE . '/([A-Za-z0-9_\-]+\.[A-Za-z0-9_\-]+)/?$', 'subpage=notification&notification_token=$matches[1]' );
	}

	/**
	 * Flush once per rules version
	 */
	public function maybe_flush_rewrite_rules() {

		$key = 'gh_notification_rewrites_version';

		if ( (int) get_option( $key ) === self::REWRITES_VERSION ) {
			return;
		}

		flush_rewrite_rules( false );
		update_option( $key, self::REWRITES_VERSION, true );
	}

	public function add_query_vars( $vars ) {
		$vars[] = 'notification_token';

		return $vars;
	}

	public function template_redirect() {

		if ( ! is_managed_page() || get_query_var( 'subpage' ) !== 'notification' ) {
			return;
		}

		$is_post = isset( $_SERVER['REQUEST_METHOD'] ) && strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) === 'POST';

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- the signed link in the address is what authorizes it
		$result = $this->handle( (string) get_query_var( 'notification_token' ), $is_post, $is_post ? wp_unslash( $_POST ) : [] );

		self::render_page( $result );
	}

	/**
	 * Work out what to show for a link, and do what it says if the form on its page was sent
	 *
	 * @param string $token
	 * @param bool   $is_post whether the form was sent
	 * @param array  $post    what was sent
	 *
	 * @return array status, title, body (HTML, already escaped)
	 */
	public function handle( string $token, bool $is_post = false, array $post = [] ): array {

		$link = Signed_Link::parse( $token );

		if ( ! $link ) {
			return self::message( 400, __( 'This link does not work', 'groundhogg' ), __( 'It looks like it was changed or copied only in part. Open the link in the email again, or change your notifications from your profile.', 'groundhogg' ), true );
		}

		if ( $link['expired'] ) {
			return self::message( 410, __( 'This link has expired', 'groundhogg' ), __( 'It was only meant to work for a while. You can change your notifications from your profile.', 'groundhogg' ), true );
		}

		$user = get_userdata( $link['user_id'] );

		if ( ! $user ) {
			return self::message( 404, __( 'This link does not work', 'groundhogg' ), __( 'The person it was made for is no longer here.', 'groundhogg' ) );
		}

		switch ( $link['action'] ) {
			case 'unsub':
				return $this->unsubscribe( $user, $link['object'], $is_post );
			case 'snooze':
			case 'complete':
				return $this->task( $user, $link['action'], absint( $link['object'] ), $is_post, $post );
		}

		return self::message( 400, __( 'This link does not work', 'groundhogg' ), __( 'It asks for something that is not possible here.', 'groundhogg' ) );
	}

	/**
	 * @param \WP_User $user
	 * @param string   $id      the notification
	 * @param bool     $is_post
	 *
	 * @return array
	 */
	protected function unsubscribe( \WP_User $user, string $id, bool $is_post ): array {

		$type = Subscriptions::get( $id );

		if ( ! $type ) {
			return self::message( 404, __( 'This link does not work', 'groundhogg' ), __( 'It is for an email that is no longer sent.', 'groundhogg' ) );
		}

		$label = esc_html( $type['label'] );

		if ( ! $is_post ) {
			return [
				'status' => 200,
				/* translators: %s: what is sent, e.g. "daily summaries of your day" */
				'title'  => sprintf( __( 'Stop receiving %s?', 'groundhogg' ), $type['label'] ),
				'body'   => '<p>' . esc_html( sprintf(
						/* translators: %s: an email address */
						__( 'They are sent to %s. You can turn them back on from your profile whenever you like.', 'groundhogg' ),
						$user->user_email
					) ) . '</p>' . self::form( [ [ 'label' => __( 'Unsubscribe', 'groundhogg' ), 'primary' => true ] ] ),
			];
		}

		Subscriptions::unsubscribe( $user->ID, $id );

		return [
			'status' => 200,
			'title'  => __( 'You are unsubscribed', 'groundhogg' ),
			/* translators: %s: what is sent, e.g. "daily summaries of your day" */
			'body'   => '<p>' . sprintf( esc_html__( 'You will not get any more %s.', 'groundhogg' ), $label ) . '</p>' . self::profile_link(),
		];
	}

	/**
	 * Snooze or complete a task
	 *
	 * @param \WP_User $user
	 * @param string   $action  snooze or complete
	 * @param int      $task_id
	 * @param bool     $is_post
	 * @param array    $post
	 *
	 * @return array
	 */
	protected function task( \WP_User $user, string $action, int $task_id, bool $is_post, array $post ): array {

		$task = new Task( $task_id );

		// The user has to be allowed to edit it now, a task that was reassigned since the email went out isn't theirs to change
		if ( ! $task->exists() || ! user_can( $user, 'edit_task', $task ) ) {
			return self::message( 404, __( 'This task is not available', 'groundhogg' ), __( 'It may have been deleted, or given to someone else.', 'groundhogg' ) );
		}

		if ( $task->is_complete() ) {
			return self::message( 200, __( 'This task is already done', 'groundhogg' ), $task->summary );
		}

		$summary = '<p><strong>' . esc_html( $task->summary ) . '</strong></p>';

		if ( $action === 'complete' ) {

			if ( ! $is_post ) {
				return [
					'status' => 200,
					'title'  => __( 'Mark this task complete?', 'groundhogg' ),
					'body'   => $summary . self::form( [ [ 'label' => __( 'Mark complete', 'groundhogg' ), 'primary' => true ] ] ),
				];
			}

			run_as_user( $user->ID, function () use ( $task ) {
				$task->update( [ 'complete' => 1 ] );
			} );

			return self::message( 200, __( 'Task completed', 'groundhogg' ), $task->summary );
		}

		if ( ! $is_post ) {

			$buttons = [];

			foreach ( self::SNOOZE_OPTIONS as $days ) {
				$buttons[] = [
					/* translators: %d: a number of days */
					'label'   => sprintf( _n( '%d day', '%d days', $days, 'groundhogg' ), $days ),
					'name'    => 'days',
					'value'   => $days,
					'primary' => $days === self::SNOOZE_OPTIONS[0],
				];
			}

			return [
				'status' => 200,
				'title'  => __( 'Snooze this task', 'groundhogg' ),
				'body'   => $summary . '<p>' . esc_html__( 'Put it off for how long?', 'groundhogg' ) . '</p>' . self::form( $buttons ),
			];
		}

		// absint() would take -1 for 1, and it's only the lengths offered
		$days = isset( $post['days'] ) && ctype_digit( (string) $post['days'] ) ? (int) $post['days'] : 0;

		if ( ! in_array( $days, self::SNOOZE_OPTIONS, true ) ) {
			return self::message( 400, __( 'This link does not work', 'groundhogg' ), __( 'That is not a length of time that a task can be snoozed for.', 'groundhogg' ) );
		}

		run_as_user( $user->ID, function () use ( $task, $days ) {
			$task->update( [ 'snooze' => $days ] );
		} );

		$due = ( new Task( $task_id ) )->get_due_date();

		return self::message( 200, __( 'Task snoozed', 'groundhogg' ), sprintf(
			/* translators: 1: the task, 2: when it is due now */
			__( '%1$s is now due %2$s.', 'groundhogg' ),
			$task->summary,
			$due->wpDateTimeFormat()
		) );
	}

	/**
	 * A page with only a message
	 *
	 * @param int    $status
	 * @param string $title
	 * @param string $message      plain text, it is escaped here
	 * @param bool   $with_profile add a link to the user's profile
	 *
	 * @return array
	 */
	protected static function message( int $status, string $title, string $message, bool $with_profile = false ): array {

		$body = '<p>' . esc_html( $message ) . '</p>';

		return [
			'status' => $status,
			'title'  => $title,
			'body'   => $body . ( $with_profile ? self::profile_link() : '' ),
		];
	}

	protected static function profile_link(): string {
		return '<p><a href="' . esc_url( admin_url( 'profile.php#groundhogg' ) ) . '">' . esc_html__( 'Change your notifications from your profile', 'groundhogg' ) . '</a></p>';
	}

	/**
	 * A form that POSTs to the page it's on
	 *
	 * @param array[] $buttons label, and optionally name, value and primary
	 *
	 * @return string
	 */
	protected static function form( array $buttons ): string {

		$html = '<form method="post" action="">';

		foreach ( $buttons as $button ) {

			$html .= sprintf(
				'<button type="submit" class="%s"%s%s>%s</button> ',
				! empty( $button['primary'] ) ? 'primary' : '',
				isset( $button['name'] ) ? ' name="' . esc_attr( $button['name'] ) . '"' : '',
				isset( $button['value'] ) ? ' value="' . esc_attr( $button['value'] ) . '"' : '',
				esc_html( $button['label'] )
			);
		}

		return $html . '</form>';
	}

	/**
	 * Print a page, and stop. It carries a token in its address, so it's kept out of caches and search results, and the
	 * address isn't passed on to anything the page links to.
	 *
	 * @param array $result status, title, body
	 */
	protected static function render_page( array $result ) {

		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}

		nocache_headers();
		status_header( $result['status'] );
		header( 'Content-Type: text/html; charset=utf-8' );
		header( 'X-Robots-Tag: noindex, nofollow' );
		header( 'Referrer-Policy: no-referrer' );

		$name = white_labeled_name();
		$name = is_string( $name ) && trim( $name ) !== '' ? trim( $name ) : get_bloginfo( 'name', 'display' );

		?>
        <!doctype html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <meta name="robots" content="noindex, nofollow">
            <meta name="referrer" content="no-referrer">
            <title><?php echo esc_html( $result['title'] . ' - ' . $name ); ?></title>
            <style>
                :root { color-scheme: light dark; }
                body { margin: 0; padding: 24px 16px; font: 16px/1.5 system-ui, sans-serif; background: #f6f9fb; color: #102640; }
                main { max-width: 440px; margin: 40px auto; padding: 24px; background: #fff; border: 1px solid #e7e9ec; border-radius: 8px; }
                h1 { margin: 0 0 16px; font-size: 20px; }
                a { color: #0075ff; }
                button { font: inherit; padding: 8px 14px; margin: 0 6px 6px 0; border: 1px solid #b6bcc4; border-radius: 5px; background: #fff; color: inherit; cursor: pointer; }
                button.primary { background: #0075ff; border-color: #0075ff; color: #fff; }
                .site { margin: 0 0 8px; font-size: 13px; color: #5c6670; }
                @media (prefers-color-scheme: dark) {
                    body { background: #11151a; color: #e6e9ec; }
                    main { background: #1a2027; border-color: #2c343d; }
                    button { background: #1a2027; border-color: #46505b; }
                    .site { color: #98a2ad; }
                }
            </style>
        </head>
        <body>
        <main>
            <p class="site"><?php echo esc_html( $name ); ?></p>
            <h1><?php echo esc_html( $result['title'] ); ?></h1>
			<?php echo $result['body']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts above ?>
        </main>
        </body>
        </html>
		<?php

		exit;
	}
}
