<?php

namespace Groundhogg\AI;

use Groundhogg\AI\Jobs\Alt_Text;
use Groundhogg\AI\Jobs\Contact_Summary;
use Groundhogg\AI\Jobs\Preview_Text;
use Groundhogg\AI\Jobs\Subject_Lines;
use Groundhogg\AI\Jobs\Translate_Email;
use Groundhogg\DB\Query\Table_Query;
use WP_Error;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_db;
use function Groundhogg\is_option_enabled;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AI on the site's own key: WordPress's AI client (7.0+), with the provider keys the owner enters in Settings ›
 * Connectors. Every feature goes through here, never through the client on its own, so availability, capabilities,
 * the monthly limit and the usage log are the same everywhere.
 *
 * It's the other way in from the MCP assistant people bring, see Groundhogg\Mcp\Build_With_AI: that one runs in their
 * own app and on their own subscription, this one runs here and is billed to the owner's key.
 *
 * includes/ai also holds an older, unfinished attempt (AI_Generator, AI_Handler and the ai.groundhogg.io actions),
 * which is switched off and separate from this.
 */
class AI {

	/**
	 * Who can use AI features, on top of the capability for what they change. Administrators and marketers have it.
	 */
	const CAP = 'use_ai';

	/**
	 * The owner can turn AI features off
	 */
	const DISABLED_OPTION = 'gh_disable_ai';

	/**
	 * The most requests a month, 0 for no limit
	 */
	const LIMIT_OPTION = 'gh_ai_monthly_limit';

	/**
	 * The settings group both are saved in, from the AI features tab
	 */
	const SETTINGS_GROUP = 'gh_ai_features';

	/**
	 * The admin page and its tab for AI features, see Groundhogg\Admin\AI\AI_Page
	 */
	const PAGE = 'gh_ai';
	const TAB  = 'features';

	/**
	 * How long whether a provider can do something is remembered for, since finding out can mean asking the provider
	 */
	const CACHE_FOR = 10 * MINUTE_IN_SECONDS;

	/**
	 * @var Job[]|null by name
	 */
	protected static $jobs = null;

	public static function init() {

		if ( ! self::is_supported() ) {
			return;
		}

		// on the AI features tab of the AI page, which saves through options.php, see Groundhogg\Admin\AI\AI_Page
		add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );

		// a key added or changed in Settings › Connectors changes what a provider can do
		add_action( 'updated_option', [ __CLASS__, 'maybe_forget_providers' ] );
		add_action( 'added_option', [ __CLASS__, 'maybe_forget_providers' ] );
		add_action( 'deleted_option', [ __CLASS__, 'maybe_forget_providers' ] );

		add_filter( 'script_loader_tag', [ __CLASS__, 'print_client_data' ], 10, 2 );
	}

	/**
	 * Give ai.js what it needs to know, only on pages that load it, since finding out can mean asking the providers
	 *
	 * @param string $tag
	 * @param string $handle
	 *
	 * @return string
	 */
	public static function print_client_data( $tag, $handle ) {

		if ( $handle !== 'groundhogg-ai' ) {
			return $tag;
		}

		$data = wp_json_encode( self::client_data() );

		return wp_get_inline_script_tag( "Groundhogg.ai = Object.assign( Groundhogg.ai || {}, { site: {$data} } );" ) . $tag;
	}

	/**
	 * Whether this WordPress has the AI client, and hasn't turned AI off with WP_AI_SUPPORT or the wp_supports_ai
	 * filter
	 *
	 * @return bool
	 */
	public static function is_supported(): bool {
		return function_exists( 'wp_ai_client_prompt' ) && function_exists( 'wp_supports_ai' ) && wp_supports_ai();
	}

	/**
	 * Whether AI features are on: supported, and not turned off in the settings
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		return self::is_supported() && ! is_option_enabled( self::DISABLED_OPTION );
	}

	/**
	 * Whether a provider the site has a key for can make this
	 *
	 * @param string $kind Job::TEXT or Job::IMAGE
	 *
	 * @return bool
	 */
	public static function can_generate( string $kind = Job::TEXT ): bool {

		if ( ! self::is_enabled() ) {
			return false;
		}

		$transient = 'gh_ai_can_' . $kind;
		$cached    = get_transient( $transient );

		if ( $cached === false ) {

			try {
				$prompt = wp_ai_client_prompt( 'Hello' );
				$can    = $kind === Job::IMAGE ? $prompt->is_supported_for_image_generation() : $prompt->is_supported_for_text_generation();
			} catch ( \Throwable $e ) {
				$can = false;
			}

			$cached = $can === true ? 'yes' : 'no';
			set_transient( $transient, $cached, self::CACHE_FOR );
		}

		/**
		 * Filter whether a provider the site has a key for can make something
		 *
		 * @param bool   $can
		 * @param string $kind text or image
		 */
		return (bool) apply_filters( 'groundhogg/ai/can_generate', $cached === 'yes', $kind );
	}

	/**
	 * Forget what the providers can do once a connector's key changes
	 *
	 * @param string $option
	 *
	 * @return void
	 */
	public static function maybe_forget_providers( $option ) {
		if ( is_string( $option ) && strpos( $option, 'connectors_' ) === 0 ) {
			self::forget_providers();
		}
	}

	public static function forget_providers() {
		delete_transient( 'gh_ai_can_' . Job::TEXT );
		delete_transient( 'gh_ai_can_' . Job::IMAGE );
	}

	/**
	 * The most requests a month, 0 for no limit
	 *
	 * @return int
	 */
	public static function get_limit(): int {
		return absint( get_option( self::LIMIT_OPTION ) );
	}

	/**
	 * When this month started, in UTC, the way the usage log's dates are stored
	 *
	 * @return string
	 */
	protected static function month_start(): string {
		return gmdate( 'Y-m-01 00:00:00' );
	}

	/**
	 * Every request sent to a provider this month, which is what the limit counts: the ones that worked, the ones that
	 * failed, since a provider can bill those too, and the ones on their way
	 *
	 * @return int
	 */
	public static function requests_this_month(): int {

		$query = new Table_Query( 'ai_usage' );
		$query->where()
		      ->in( 'status', [ 'pending', 'complete', 'failed' ] )
		      ->greaterThanEqualTo( 'date_created', self::month_start() );

		return absint( $query->count() );
	}

	/**
	 * The tokens used this month
	 *
	 * @return int
	 */
	public static function tokens_this_month(): int {
		global $wpdb;

		$table = get_db( 'ai_usage' )->table_name;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- the table name is ours, the values are prepared
		return absint( $wpdb->get_var( $wpdb->prepare( "SELECT SUM(total_tokens) FROM {$table} WHERE status = %s AND date_created >= %s", 'complete', self::month_start() ) ) );
	}

	/**
	 * This month's use of each job: the requests that worked, their tokens, and the ones that failed
	 *
	 * @return array[] by job name, each [ requests, tokens, failed ]
	 */
	public static function usage_by_job(): array {
		global $wpdb;

		$table = get_db( 'ai_usage' )->table_name;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- the table name is ours, the values are prepared
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT job, SUM(status = 'complete') AS requests, SUM(CASE WHEN status = 'complete' THEN total_tokens ELSE 0 END) AS tokens, SUM(status = 'failed') AS failed FROM {$table} WHERE date_created >= %s GROUP BY job ORDER BY requests DESC", self::month_start() ) );

		$usage = [];

		foreach ( (array) $rows as $row ) {
			$usage[ $row->job ] = [
				'requests' => absint( $row->requests ),
				'tokens'   => absint( $row->tokens ),
				'failed'   => absint( $row->failed ),
			];
		}

		return $usage;
	}

	/**
	 * Whether this month's limit is used up
	 *
	 * @return bool
	 */
	public static function limit_reached(): bool {
		$limit = self::get_limit();

		return $limit > 0 && self::requests_this_month() >= $limit;
	}

	/**
	 * The jobs, by name
	 *
	 * @return Job[]
	 */
	public static function jobs(): array {

		if ( self::$jobs !== null ) {
			return self::$jobs;
		}

		/**
		 * Filter the jobs the site can ask an AI provider to do
		 *
		 * @param Job[] $jobs
		 */
		$jobs = apply_filters( 'groundhogg/ai/jobs', [
			new Alt_Text(),
			new Subject_Lines(),
			new Preview_Text(),
			new Contact_Summary(),
			new Translate_Email(),
		] );

		self::$jobs = [];

		foreach ( $jobs as $job ) {
			if ( $job instanceof Job ) {
				self::$jobs[ $job->get_name() ] = $job;
			}
		}

		return self::$jobs;
	}

	/**
	 * Forget the jobs, so the filter runs again
	 *
	 * @return void
	 */
	public static function reset_jobs() {
		self::$jobs = null;
	}

	/**
	 * @param string $name
	 *
	 * @return Job|null
	 */
	public static function get_job( string $name ) {
		return self::jobs()[ $name ] ?? null;
	}

	/**
	 * Whether a job is switched on. They all are: this is where a setting to turn single features off goes, or a
	 * snippet in the meantime.
	 *
	 * @param Job $job
	 *
	 * @return bool
	 */
	public static function job_is_on( Job $job ): bool {

		/**
		 * Filter whether an AI job is switched on
		 *
		 * @param bool   $on
		 * @param string $name the job's name, like alt-text
		 * @param Job    $job
		 */
		return (bool) apply_filters( 'groundhogg/ai/job_is_on', true, $job->get_name(), $job );
	}

	/**
	 * Whether the current user can use a job right now: AI and the job are on, they're allowed, and a provider can do
	 * it. The monthly limit is left to run(), which says so when it's used up.
	 *
	 * @param Job $job
	 *
	 * @return bool
	 */
	public static function can_use_job( Job $job ): bool {
		return self::is_enabled()
		       && self::job_is_on( $job )
		       && current_user_can( self::CAP )
		       && current_user_can( $job->get_capability() )
		       && self::can_generate( $job->get_kind() );
	}

	/**
	 * Do a job for the current user
	 *
	 * @param string $name  the job's name
	 * @param array  $input
	 *
	 * @return array|WP_Error what the job responds with, and the tokens it used
	 */
	public static function run( string $name, array $input = [] ) {

		$job = self::get_job( $name );

		if ( ! $job ) {
			return new WP_Error( 'ai_unknown_job', __( 'There is no such AI job.', 'groundhogg' ), [ 'status' => 404 ] );
		}

		if ( ! self::is_enabled() ) {
			return new WP_Error( 'ai_unavailable', __( 'AI features aren\'t turned on for this site.', 'groundhogg' ), [ 'status' => 503 ] );
		}

		if ( ! self::job_is_on( $job ) ) {
			return new WP_Error( 'ai_job_off', __( 'This AI feature is turned off for this site.', 'groundhogg' ), [ 'status' => 403 ] );
		}

		if ( ! current_user_can( self::CAP ) || ! current_user_can( $job->get_capability() ) ) {
			return new WP_Error( 'ai_forbidden', __( 'You don\'t have permission to use this.', 'groundhogg' ), [ 'status' => 403 ] );
		}

		if ( self::limit_reached() ) {
			return self::limit_error();
		}

		if ( ! self::can_generate( $job->get_kind() ) ) {
			return new WP_Error( 'ai_no_provider', __( 'None of the AI providers on this site can do this. A key for one can be added in Settings › Connectors.', 'groundhogg' ), [ 'status' => 503 ] );
		}

		$input = $job->validate( $input );

		if ( is_wp_error( $input ) ) {

			// unless the job said, like 403 for a contact they can't see
			if ( empty( $input->get_error_data()['status'] ) ) {
				$input->add_data( [ 'status' => 400 ] );
			}

			return $input;
		}

		// counted before it's sent, so requests at the same moment can't all take the last of the month's
		$log_id = self::claim( $job );

		if ( ! $log_id ) {
			return self::limit_error();
		}

		try {
			$result = $job->generate( $job->prompt( $input ) );
		} catch ( \Throwable $e ) {
			$result = new WP_Error( 'ai_failed', $e->getMessage() );
		}

		// nothing was sent, so nothing to count
		if ( is_wp_error( $result ) && $result->get_error_code() === 'ai_not_supported' ) {
			get_db( 'ai_usage' )->delete( $log_id );
			$result->add_data( [ 'status' => 503 ] );

			return $result;
		}

		if ( is_wp_error( $result ) ) {

			self::log( $job, null, $result, $log_id );

			// what the provider said is for someone who can fix it, like a key without credit
			return new WP_Error( 'ai_failed', current_user_can( 'manage_options' ) ? sprintf(
			/* translators: %s: the error the AI provider gave */
				__( 'The AI provider couldn\'t do this: %s', 'groundhogg' ),
				$result->get_error_message()
			) : __( 'The AI provider couldn\'t do this right now. Try again, or ask an administrator to look at the AI page.', 'groundhogg' ), [ 'status' => 502 ] );
		}

		$usage = self::log( $job, $result, null, $log_id );

		$response = $job->respond( $result, $input );

		if ( is_wp_error( $response ) ) {
			$response->add_data( [ 'status' => 502 ] );

			return $response;
		}

		return array_merge( $response, [
			'usage' => $usage,
		] );
	}

	/**
	 * Why a job can't run
	 *
	 * @return WP_Error
	 */
	protected static function limit_error(): WP_Error {
		return new WP_Error( 'ai_limit_reached', sprintf(
		/* translators: %s: the number of requests allowed a month */
			__( 'This month\'s %s AI requests are used up. They start again on the first of next month, or the limit can be raised in the settings.', 'groundhogg' ),
			number_format_i18n( self::get_limit() )
		), [ 'status' => 429 ] );
	}

	/**
	 * Take one of the month's requests: a pending row in the usage log, added only while the limit isn't used up. With
	 * a limit, under a database lock, so two requests at the same moment can't both take the last one.
	 *
	 * @param Job $job
	 *
	 * @return int the row's ID, 0 when the limit is used up
	 */
	protected static function claim( Job $job ): int {
		global $wpdb;

		$limit  = self::get_limit();
		$lock   = $wpdb->prefix . 'gh_ai_requests';
		$locked = $limit && $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $lock ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		try {
			if ( $limit && self::requests_this_month() >= $limit ) {
				return 0;
			}

			return absint( get_db( 'ai_usage' )->add( [
				'job'     => $job->get_name(),
				'user_id' => get_current_user_id(),
				'status'  => 'pending',
			] ) );
		} finally {
			if ( $locked ) {
				$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			}
		}
	}

	/**
	 * Note a request in the usage log
	 *
	 * @param Job                                                      $job
	 * @param \WordPress\AiClient\Results\DTO\GenerativeAiResult|null $result
	 * @param WP_Error|null                                            $error
	 * @param int                                                      $log_id the row taken for it, see claim()
	 *
	 * @return array the tokens used, and the provider and model
	 */
	public static function log( Job $job, $result = null, $error = null, int $log_id = 0 ): array {

		$row = [
			'job'     => $job->get_name(),
			'user_id' => get_current_user_id(),
			'status'  => $error ? 'failed' : 'complete',
		];

		if ( $error ) {
			$row['error_code'] = substr( (string) $error->get_error_code(), 0, 50 );
		}

		if ( $result ) {
			$tokens = $result->getTokenUsage();

			$row += [
				'provider'          => substr( $result->getProviderMetadata()->getId(), 0, 50 ),
				'model'             => substr( $result->getModelMetadata()->getId(), 0, 100 ),
				'prompt_tokens'     => $tokens->getPromptTokens(),
				'completion_tokens' => $tokens->getCompletionTokens(),
				'total_tokens'      => $tokens->getTotalTokens(),
			];
		}

		if ( $log_id ) {
			get_db( 'ai_usage' )->update( $log_id, $row );
		} else {
			get_db( 'ai_usage' )->add( $row );
		}

		return [
			'provider' => $row['provider'] ?? '',
			'model'    => $row['model'] ?? '',
			'tokens'   => $row['total_tokens'] ?? 0,
		];
	}

	/**
	 * What the browser needs to know, see ai.js
	 *
	 * @return array
	 */
	public static function client_data(): array {

		$enabled = self::is_enabled();
		$can_use = $enabled && current_user_can( self::CAP );

		return [
			'supported'     => self::is_supported(),
			'enabled'       => $enabled,
			'canUse'        => $can_use,
			'text'          => $can_use && self::can_generate( Job::TEXT ),
			'image'         => $can_use && self::can_generate( Job::IMAGE ),
			'limitReached'  => $can_use && self::limit_reached(),
			// the jobs this person can use, a feature shows its button when its job is here, see Groundhogg.ai.has()
			'jobs'          => array_map( function ( Job $job ) use ( $can_use ) {
				return $can_use && self::can_use_job( $job );
			}, self::jobs() ),
			'connectorsUrl' => admin_url( 'options-connectors.php' ),
			'settingsUrl'   => self::page_url(),
		];
	}

	/**
	 * The AI features tab of the AI page
	 *
	 * @return string
	 */
	public static function page_url(): string {
		return admin_page_url( self::PAGE, [ 'tab' => self::TAB ] );
	}

	/**
	 * Where AI features stand, in one word, for the AI page and Site Health
	 *
	 * @return string unsupported, disabled, no_provider, limit_reached or ready
	 */
	public static function status(): string {

		if ( ! self::is_supported() ) {
			return 'unsupported';
		}

		if ( ! self::is_enabled() ) {
			return 'disabled';
		}

		if ( ! self::can_generate( Job::TEXT ) ) {
			return 'no_provider';
		}

		return self::limit_reached() ? 'limit_reached' : 'ready';
	}

	/**
	 * The two settings, saved from the AI features tab through options.php, which only lets people who can
	 * manage_options save them
	 *
	 * @return void
	 */
	public static function register_settings() {

		register_setting( self::SETTINGS_GROUP, self::DISABLED_OPTION, [
			'type'              => 'string',
			'sanitize_callback' => function ( $value ) {
				return $value === 'on' ? 'on' : '';
			},
		] );

		register_setting( self::SETTINGS_GROUP, self::LIMIT_OPTION, [
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
		] );
	}

}
