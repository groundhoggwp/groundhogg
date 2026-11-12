<?php

namespace Groundhogg\Abilities\Setup;

use Groundhogg\Abilities\Ability;
use Groundhogg\Plugin;
use function Groundhogg\get_primary_owner;

/**
 * What's left to set up, and what's wrong, so an agent setting up a site to someone's spec knows where it stands and
 * can keep going until everything is good.
 *
 * Two kinds of check:
 *
 * - "health": every Site Health test (see Site_Health::run()), the things that stop automation and email working -
 *   the queue, cron, the email service, failed events, the database, permalinks.
 * - "setup": things a fresh install leaves undone that Site Health doesn't judge - business details for the footer,
 *   policy links, a from address on the site's own domain, the default owner, the setup being marked done.
 *
 * Each check that isn't good says how it's fixed: by the agent (with the abilities to use), by a person (with what to
 * ask them for), or either. The Site Health tests are written for people, so the hints for them live here, keyed by
 * test id. A test an add-on adds through groundhogg/site_health/tests gets no hint and is left to a person - an add-on
 * can supply one with the groundhogg/abilities/setup_status/fixes filter.
 */
class Get_Setup_Status extends Ability {

	protected const NAME       = 'groundhogg/get-setup-status';
	protected const CATEGORY   = 'groundhogg-setup';
	protected const CAPABILITY = 'manage_options';

	protected const READONLY   = true;
	protected const IDEMPOTENT = true;

	protected function get_args(): array {

		$fix = [
			'type'       => 'object',
			'properties' => [
				'by'        => [
					'type'        => 'string',
					'enum'        => [ 'agent', 'person', 'either' ],
					'description' => __( 'Who can fix it. "person" means something only a human can do: create an account, enter a password or key, change the server or DNS. Ask them, explaining what and why.', 'groundhogg' ),
				],
				'how'       => [ 'type' => 'string' ],
				'abilities' => [
					'type'        => 'array',
					'items'       => [ 'type' => 'string' ],
					'description' => __( 'Abilities that fix it, or help find out more.', 'groundhogg' ),
				],
			],
		];

		return [
			'label'       => __( 'Get Setup Status', 'groundhogg' ),
			'description' => __( 'Check what\'s left to set up on this site and what\'s stopping automation or email from working: business details, policies, the sender, the email service, cron, the event queue, failed events, the database and more. Each check that isn\'t good says whether you can fix it (and with which abilities) or what to ask a person to do. Call it first when setting up a site, and again after making changes. See groundhogg/get-authoring-guide topic "setup" for the whole process.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => [
					'only_problems' => [
						'type'        => 'boolean',
						'default'     => false,
						'description' => __( 'Leave out checks that are already good.', 'groundhogg' ),
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'summary' => [
						'type'        => 'object',
						'description' => __( 'How many checks have each status.', 'groundhogg' ),
						'properties'  => [
							'good'        => [ 'type' => 'integer' ],
							'recommended' => [ 'type' => 'integer' ],
							'critical'    => [ 'type' => 'integer' ],
						],
					],
					'checks'  => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'id'      => [ 'type' => 'string' ],
								'kind'    => [
									'type' => 'string',
									'enum' => [ 'setup', 'health' ],
								],
								'status'  => [
									'type'        => 'string',
									'enum'        => [ 'good', 'recommended', 'critical' ],
									'description' => __( '"critical" stops something working, "recommended" should be fixed.', 'groundhogg' ),
								],
								'label'   => [ 'type' => 'string' ],
								'details' => [ 'type' => 'string' ],
								'fix'     => array_merge( $fix, [ 'type' => [ 'object', 'null' ] ] ),
								'links'   => [
									'type'        => 'array',
									'description' => __( 'Admin screens where a person can fix it.', 'groundhogg' ),
									'items'       => [
										'type'       => 'object',
										'properties' => [
											'text' => [ 'type' => 'string' ],
											'url'  => [ 'type' => 'string' ],
										],
									],
								],
							],
						],
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$checks = array_merge( $this->setup_checks(), $this->health_checks() );

		$summary = [ 'good' => 0, 'recommended' => 0, 'critical' => 0 ];

		foreach ( $checks as $check ) {
			$summary[ $check['status'] ] ++;
		}

		if ( ! empty( $input['only_problems'] ) ) {
			$checks = array_values( array_filter( $checks, function ( $check ) {
				return $check['status'] !== 'good';
			} ) );
		}

		return [
			'summary' => $summary,
			'checks'  => $checks,
		];
	}

	/**
	 * A check, in the shape the output schema describes
	 *
	 * @param string     $id
	 * @param string     $kind    setup or health
	 * @param string     $status  good, recommended or critical
	 * @param string     $label
	 * @param string     $details plain text
	 * @param array|null $fix     [ by, how, abilities ], left out when it's good
	 * @param array      $links   url => text
	 *
	 * @return array
	 */
	protected function check( string $id, string $kind, string $status, string $label, string $details, $fix = null, array $links = [] ) {

		if ( ! in_array( $status, [ 'good', 'recommended', 'critical' ], true ) ) {
			$status = 'recommended';
		}

		return [
			'id'      => $id,
			'kind'    => $kind,
			'status'  => $status,
			'label'   => $label,
			'details' => $details,
			'fix'     => $status === 'good' || ! $fix ? null : [
				'by'        => $fix[0],
				'how'       => $fix[1],
				'abilities' => $fix[2] ?? [],
			],
			'links'   => array_map( function ( $url, $text ) {
				return [ 'text' => $text, 'url' => $url ];
			}, array_keys( $links ), $links ),
		];
	}

	/**
	 * What a fresh install leaves undone
	 *
	 * @return array[]
	 */
	protected function setup_checks() {

		$settings = Plugin::instance()->settings;
		$checks   = [];

		// the footer of every marketing email shows these, anti-spam laws require a postal address
		$missing = array_filter( [ 'business_name', 'street_address_1', 'city', 'country' ], function ( $id ) use ( $settings ) {
			return trim( (string) $settings->get_option( $id ) ) === '';
		} );

		$checks[] = empty( $missing )
			? $this->check( 'business_info', 'setup', 'good', __( 'Business details are filled in', 'groundhogg' ),
				__( 'The business name and address show in the footer of every email.', 'groundhogg' ) )
			: $this->check( 'business_info', 'setup', 'recommended', __( 'Business details are missing', 'groundhogg' ),
				/* translators: %s: setting ids, e.g. "business_name, city" */
				sprintf( __( 'Not set: %s. The business name and postal address show in the footer of every email, and anti-spam laws require them in marketing email.', 'groundhogg' ), implode( ', ', $missing ) ),
				[ 'agent', __( 'Ask for the business name, address and phone number if you don\'t have them, then save them with groundhogg/update-settings (group "business_info").', 'groundhogg' ), [ 'groundhogg/update-settings' ] ] );

		// '#' is what a site gets before anyone sets them
		$unset_policies = array_filter( [ 'privacy_policy', 'terms' ], function ( $id ) use ( $settings ) {
			return in_array( trim( (string) $settings->get_option( $id ) ), [ '', '#' ], true );
		} );

		$wp_privacy = get_privacy_policy_url();

		$checks[] = empty( $unset_policies )
			? $this->check( 'policies', 'setup', 'good', __( 'Policy links are set', 'groundhogg' ),
				__( 'Emails, forms and the preferences center link to the privacy policy and terms.', 'groundhogg' ) )
			: $this->check( 'policies', 'setup', 'recommended', __( 'Policy links are not set', 'groundhogg' ),
				/* translators: %s: setting ids, e.g. "privacy_policy, terms" */
				sprintf( __( 'Not set: %s. Emails, forms and the preferences center link to them, and consent checkboxes need a privacy policy to point to.', 'groundhogg' ), implode( ', ', $unset_policies ) )
				/* translators: %s: a URL */
				. ( $wp_privacy ? ' ' . sprintf( __( 'WordPress\'s own privacy policy page is %s.', 'groundhogg' ), $wp_privacy ) : '' ),
				[ 'agent', __( 'Ask for the links, or confirm the site\'s own policy pages, then save them with groundhogg/update-settings (privacy_policy, terms).', 'groundhogg' ), [ 'groundhogg/update-settings' ] ] );

		$checks[] = $this->sender_check();

		$owner = get_primary_owner();

		$checks[] = $owner && user_can( $owner, 'view_contacts' )
			? $this->check( 'primary_user', 'setup', 'good', __( 'There is a default contact owner', 'groundhogg' ),
				/* translators: %s: a person's name */
				sprintf( __( 'New contacts without an owner are assigned to %s.', 'groundhogg' ), $owner->display_name ) )
			: $this->check( 'primary_user', 'setup', 'recommended', __( 'There is no default contact owner', 'groundhogg' ),
				__( 'New contacts that don\'t get an owner any other way should be assigned to someone, who also appears as the sender for owner-based emails.', 'groundhogg' ),
				[ 'agent', __( 'Pick an owner with groundhogg/list-owners, asking who if it isn\'t clear, and save their user ID with groundhogg/update-settings (primary_user).', 'groundhogg' ), [ 'groundhogg/list-owners', 'groundhogg/update-settings' ] ] );

		$checks[] = $settings->is_option_enabled( 'guided_setup_finished' )
			? $this->check( 'guided_setup_finished', 'setup', 'good', __( 'Setup is marked as done', 'groundhogg' ),
				__( 'The reminder to finish the guided setup is hidden.', 'groundhogg' ) )
			: $this->check( 'guided_setup_finished', 'setup', 'recommended', __( 'Setup is not marked as done', 'groundhogg' ),
				__( 'The admin shows a reminder to finish the guided setup until it is.', 'groundhogg' ),
				[ 'agent', __( 'Once the other setup checks are good, or the person has decided to leave them, set guided_setup_finished to true with groundhogg/update-settings.', 'groundhogg' ), [ 'groundhogg/update-settings' ] ] );

		return $checks;
	}

	/**
	 * Whether email is sent from the site's own domain. Sending as a domain the email service isn't set up for, a
	 * gmail.com address say, fails the checks inboxes do and ends up in spam.
	 *
	 * @return array
	 */
	protected function sender_check() {

		$from = Plugin::instance()->settings->get_option( 'override_from_email' ) ?: get_bloginfo( 'admin_email' );

		$from_domain = strtolower( (string) substr( strrchr( $from, '@' ), 1 ) );
		$site_domain = strtolower( preg_replace( '/^www\./', '', (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) );

		// the same domain, or one is a subdomain of the other, e.g. mail.example.com and example.com
		$related = $from_domain && $site_domain && (
				$from_domain === $site_domain
				|| str_ends_with( $from_domain, '.' . $site_domain )
				|| str_ends_with( $site_domain, '.' . $from_domain )
			);

		if ( $related ) {
			return $this->check( 'sender', 'setup', 'good', __( 'Email is sent from the site\'s domain', 'groundhogg' ),
				/* translators: %s: an email address */
				sprintf( __( 'The default sender is %s.', 'groundhogg' ), $from ) );
		}

		return $this->check( 'sender', 'setup', 'recommended', __( 'Email is sent from another domain', 'groundhogg' ),
			/* translators: 1: an email address, 2: a domain */
			sprintf( __( 'The default sender is %1$s, which isn\'t on this site\'s domain, %2$s. Unless the email service is set up to send for that domain, inboxes will treat the email as suspicious.', 'groundhogg' ), $from, $site_domain ),
			[ 'either', __( 'Ask which address email should come from, and save it with groundhogg/update-settings (override_from_email, override_from_name). The domain also needs authenticating (SPF, DKIM, DMARC) with the email service, which a person does in their DNS.', 'groundhogg' ), [ 'groundhogg/update-settings', 'groundhogg/list-sender-profiles' ] ] );
	}

	/**
	 * Every Site Health test, with how each is fixed
	 *
	 * @return array[]
	 */
	protected function health_checks() {

		$fixes = $this->health_fixes();
		$checks = [];

		foreach ( Plugin::instance()->site_health->run() as $id => $result ) {

			if ( ! is_array( $result ) ) {
				continue;
			}

			// paragraphs to plain text
			$details = trim( html_entity_decode( wp_strip_all_tags( str_replace( '</p>', "</p>\n\n", (string) ( $result['description'] ?? '' ) ) ), ENT_QUOTES ) );

			$checks[] = $this->check(
				$id,
				'health',
				(string) ( $result['status'] ?? 'recommended' ),
				wp_strip_all_tags( (string) ( $result['label'] ?? $id ) ),
				$details,
				$fixes[ $id ] ?? [ 'person', __( 'See the details and the links.', 'groundhogg' ) ],
				$result['links'] ?? []
			);
		}

		return $checks;
	}

	/**
	 * How each Site Health test's problem is fixed, by test id
	 *
	 * @return array[] id => [ by, how, abilities ]
	 */
	protected function health_fixes() {

		$fixes = [
			'event_queue'      => [ 'either', __( 'Usually the queue isn\'t being run often enough: set up the cron job (see the cron check). Events that are already late can be run or cancelled by a person from the link.', 'groundhogg' ), [ 'groundhogg/get-cron-status', 'groundhogg/set-up-cron', 'groundhogg/query-table' ] ],
			'cron'             => [ 'either', __( 'Install the cron file with groundhogg/set-up-cron, then a person with access to the server or host creates the cron job that calls it every minute. groundhogg/get-cron-status gives the exact URL or command, and says when the job is working.', 'groundhogg' ), [ 'groundhogg/get-cron-status', 'groundhogg/set-up-cron' ] ],
			'email_sending'    => [ 'agent', __( 'Pick a service that sends for each message type with groundhogg/update-settings (wordpress_email_service, transactional_email_service, marketing_email_service). On what looks like a staging site, only set allow_email_in_staging if the person confirms this is their live site.', 'groundhogg' ), [ 'groundhogg/update-settings', 'groundhogg/list-settings' ] ],
			'email_service'    => [ 'person', __( 'Email needs a dedicated sending service. A person connects one: a sending service offered in the email settings (they sign in to its account there), or an SMTP or email API add-on or plugin with their credentials. You can install a licensed add-on with groundhogg/install-extension and, once it\'s connected, choose it with groundhogg/update-settings (marketing_email_service). Never ask for passwords or API keys in chat - the person enters them in the settings.', 'groundhogg' ), [ 'groundhogg/list-extensions', 'groundhogg/install-extension', 'groundhogg/update-settings' ] ],
			'failed_events'    => [ 'agent', __( 'Look at the failed events and their error messages with groundhogg/query-table (table "events", status "failed") to find the cause, usually a step or email that needs fixing.', 'groundhogg' ), [ 'groundhogg/query-table', 'groundhogg/get-flow' ] ],
			'failed_emails'    => [ 'agent', __( 'Look at the failed emails and their errors with groundhogg/query-table (table "email_log"). Usually the email service needs reconnecting, which a person does.', 'groundhogg' ), [ 'groundhogg/query-table' ] ],
			'background_tasks' => [ 'either', __( 'Background tasks run on WordPress cron, so check it with groundhogg/get-cron-status (wordpress_cron) first: if it\'s turned off and not running, a person needs to add the cron job that calls wp-cron.php. groundhogg/query-table (table "background_tasks") shows the stuck tasks.', 'groundhogg' ), [ 'groundhogg/get-cron-status', 'groundhogg/query-table' ] ],
			'database'         => [ 'person', __( 'A person runs the installation again from the link, which creates missing tables and runs pending updates.', 'groundhogg' ) ],
			'permalinks'       => [ 'person', __( 'A person changes Settings › Permalinks to anything but Plain.', 'groundhogg' ) ],
			'safe_mode'        => [ 'person', __( 'A person turns off safe mode from the link, once whatever it was turned on for is solved.', 'groundhogg' ) ],
			'bounces'          => [ 'person', __( 'Checking bounces needs the PHP IMAP extension, which the person\'s host turns on.', 'groundhogg' ) ],
			'licenses'         => [ 'either', __( 'Check them with groundhogg/check-license. A person renews or buys a license and gives you the key to activate with groundhogg/activate-license.', 'groundhogg' ), [ 'groundhogg/check-license', 'groundhogg/activate-license' ] ],
		];

		/**
		 * Filter how get-setup-status says each Site Health test is fixed, e.g. for one an add-on adds
		 *
		 * @param array[] $fixes test id => [ by (agent, person or either), how, abilities ]
		 */
		return apply_filters( 'groundhogg/abilities/setup_status/fixes', $fixes );
	}
}
