<?php

use Groundhogg\Admin\AI\AI_Page;
use Groundhogg\AI\AI;
use Groundhogg\AI\Job;
use Groundhogg\Mcp\Connection;
use Groundhogg\AI\Jobs\Alt_Text;
use Groundhogg\Contact;
use Groundhogg\Plugin;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\ModelMessage;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Results\DTO\Candidate;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\DTO\TokenUsage;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_db;

/**
 * A job that answers with whatever it's given instead of asking a provider
 */
class Fake_AI_Job extends Job {

	/**
	 * @var GenerativeAiResult|WP_Error what the provider "made"
	 */
	public $result;

	/**
	 * @var int how many times a provider would have been asked
	 */
	public $asked = 0;

	public function get_name(): string {
		return 'fake-job';
	}

	public function get_label(): string {
		return 'Fake job';
	}

	public function get_capability(): string {
		return 'view_contacts';
	}

	public function validate( array $input ) {
		return empty( $input['bad'] ) ? $input : new WP_Error( 'bad_input', 'That input is no good.' );
	}

	public function prompt( array $input ) {
		return null;
	}

	/**
	 * @var callable|null run while the provider would be answering
	 */
	public $during = null;

	public function generate( $prompt ) {
		$this->asked ++;

		if ( is_callable( $this->during ) ) {
			call_user_func( $this->during );
		}

		return $this->result;
	}

	public function respond( $result, array $input ) {
		return [ 'text' => $result->toText() ];
	}
}

/**
 * AI on the site's own key: the checks before anything is sent, the monthly limit, the usage log, the REST route, the
 * alt text job and Site Health. No provider is asked: the fake job answers, and whether a provider can do something is
 * filtered.
 */
class AI_Tests extends GH_UnitTestCase {

	/**
	 * @var Fake_AI_Job
	 */
	protected $job;

	public function set_up() {
		parent::set_up();

		if ( ! AI::is_supported() ) {
			$this->markTestSkipped( 'Needs WordPress 7.0 for the AI client.' );
		}

		$this->job         = new Fake_AI_Job();
		$this->job->result = self::result( 'Hello there', 12, 8 );

		add_filter( 'groundhogg/ai/jobs', [ $this, 'add_job' ] );
		add_filter( 'groundhogg/ai/can_generate', '__return_true' );
		AI::reset_jobs();

		get_db( 'ai_usage' )->truncate();
		delete_option( AI::DISABLED_OPTION );
		delete_option( AI::LIMIT_OPTION );

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	public function tear_down() {
		remove_filter( 'groundhogg/ai/jobs', [ $this, 'add_job' ] );
		remove_all_filters( 'groundhogg/ai/can_generate' );
		AI::reset_jobs();
		parent::tear_down();
	}

	public function add_job( $jobs ) {
		$jobs[] = $this->job;

		return $jobs;
	}

	protected static function result( string $text, int $prompt_tokens, int $completion_tokens ): GenerativeAiResult {
		return new GenerativeAiResult(
			'result-1',
			[ new Candidate( new ModelMessage( [ new MessagePart( $text ) ] ), FinishReasonEnum::stop() ) ],
			new TokenUsage( $prompt_tokens, $completion_tokens, $prompt_tokens + $completion_tokens ),
			new ProviderMetadata( 'fake-provider', 'Fake Provider', ProviderTypeEnum::cloud() ),
			new ModelMetadata( 'fake-model', 'Fake Model', [], [] )
		);
	}

	public function test_a_job_runs_and_is_logged() {

		$result = AI::run( 'fake-job', [ 'anything' => 1 ] );

		$this->assertNotWPError( $result );
		$this->assertSame( 'Hello there', $result['text'] );
		$this->assertSame( [ 'provider' => 'fake-provider', 'model' => 'fake-model', 'tokens' => 20 ], $result['usage'] );

		$this->assertSame( 1, AI::requests_this_month() );
		$this->assertSame( 20, AI::tokens_this_month() );

		$rows = get_db( 'ai_usage' )->query( [] );

		$this->assertCount( 1, $rows );
		$this->assertSame( 'fake-job', $rows[0]->job );
		$this->assertEquals( get_current_user_id(), $rows[0]->user_id );
		$this->assertSame( 'complete', $rows[0]->status );
	}

	public function test_a_failure_is_logged_and_counted() {

		$this->job->result = new WP_Error( 'rate_limited', 'Too many requests.' );

		$result = AI::run( 'fake-job' );

		$this->assertWPError( $result );
		$this->assertSame( 'ai_failed', $result->get_error_code() );
		$this->assertStringContainsString( 'Too many requests.', $result->get_error_message(), 'an administrator sees what the provider said' );
		$this->assertSame( 502, $result->get_error_data()['status'] );

		// a provider can bill one that failed, so it counts toward the limit
		$this->assertSame( 1, AI::requests_this_month() );
		$this->assertSame( 0, AI::tokens_this_month() );
		$this->assertSame( 'failed', get_db( 'ai_usage' )->query( [] )[0]->status );

		// anyone else gets told to ask an administrator, without the provider's message
		$marketer = self::factory()->user->create( [ 'role' => 'marketer' ] );
		get_userdata( $marketer )->add_cap( AI::CAP );
		wp_set_current_user( $marketer );

		$result = AI::run( 'fake-job' );
		$this->assertStringNotContainsString( 'Too many requests.', $result->get_error_message() );
	}

	public function test_a_request_is_counted_before_it_is_sent() {

		update_option( AI::LIMIT_OPTION, 1 );

		// while it's on its way, it already counts, so another at the same moment can't take the last one
		$this->job->result = self::result( 'Hello there', 1, 1 );
		$during            = null;

		$this->job->during = function () use ( &$during ) {
			$during = [ AI::requests_this_month(), get_db( 'ai_usage' )->query( [] )[0]->status ];
		};

		$this->assertNotWPError( AI::run( 'fake-job' ) );
		$this->assertSame( [ 1, 'pending' ], $during );
		$this->assertSame( 'complete', get_db( 'ai_usage' )->query( [] )[0]->status, 'the same row, finished' );
		$this->assertSame( 1, get_db( 'ai_usage' )->count() );
	}

	/**
	 * When the provider can't do the job, nothing was sent, so it isn't logged or blamed on the provider
	 */
	public function test_a_job_no_provider_can_do_is_not_logged() {

		$this->job->result = new WP_Error( 'ai_not_supported', 'None of the AI providers on this site can do this.' );

		$result = AI::run( 'fake-job' );

		$this->assertSame( 'ai_not_supported', $result->get_error_code() );
		$this->assertSame( 'None of the AI providers on this site can do this.', $result->get_error_message() );
		$this->assertSame( 503, $result->get_error_data()['status'] );
		$this->assertSame( 0, get_db( 'ai_usage' )->count() );
	}

	public function test_the_monthly_limit_pauses_ai() {

		update_option( AI::LIMIT_OPTION, 1 );

		$this->assertNotWPError( AI::run( 'fake-job' ) );
		$this->assertTrue( AI::limit_reached() );

		$result = AI::run( 'fake-job' );

		$this->assertSame( 'ai_limit_reached', $result->get_error_code() );
		$this->assertSame( 429, $result->get_error_data()['status'] );
		$this->assertSame( 1, $this->job->asked );
	}

	/**
	 * Nothing is sent when AI is turned off, there's no provider, the person isn't allowed, or the input is wrong
	 */
	public function test_nothing_is_sent_unless_everything_checks_out() {

		$this->assertSame( 'ai_unknown_job', AI::run( 'no-such-job' )->get_error_code() );

		update_option( AI::DISABLED_OPTION, 'on' );
		$this->assertSame( 'ai_unavailable', AI::run( 'fake-job' )->get_error_code() );
		delete_option( AI::DISABLED_OPTION );

		remove_all_filters( 'groundhogg/ai/can_generate' );
		add_filter( 'groundhogg/ai/can_generate', '__return_false' );
		$this->assertSame( 'ai_no_provider', AI::run( 'fake-job' )->get_error_code() );
		remove_all_filters( 'groundhogg/ai/can_generate' );
		add_filter( 'groundhogg/ai/can_generate', '__return_true' );

		$bad = AI::run( 'fake-job', [ 'bad' => true ] );
		$this->assertSame( 'bad_input', $bad->get_error_code() );
		$this->assertSame( 400, $bad->get_error_data()['status'] );

		// use_ai, but not the job's own capability
		$user = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		get_userdata( $user )->add_cap( AI::CAP );
		wp_set_current_user( $user );
		$this->assertSame( 'ai_forbidden', AI::run( 'fake-job' )->get_error_code() );

		// the job's capability, but not use_ai
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'sales_rep' ] ) );
		$this->assertSame( 'ai_forbidden', AI::run( 'fake-job' )->get_error_code() );

		$this->assertSame( 0, $this->job->asked );
		$this->assertSame( 0, get_db( 'ai_usage' )->count() );
	}

	public function test_administrators_and_marketers_can_use_ai() {

		// the roles as installed, see Main_Roles::get_other_caps()
		Plugin::instance()->roles->add_caps();
		wp_roles()->for_site();

		foreach ( [ 'administrator' => true, 'marketer' => true, 'sales_manager' => false, 'sales_rep' => false ] as $role => $can ) {
			$this->assertSame( $can, get_role( $role )->has_cap( AI::CAP ), $role );
		}
	}

	public function test_the_rest_route() {

		$request = new WP_REST_Request( 'POST', '/gh/v4/ai/fake-job' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( [ 'anything' => 1 ] ) );

		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'Hello there', $response->get_data()['text'] );

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'sales_rep' ] ) );

		$this->assertSame( 403, rest_do_request( $request )->get_status() );
	}

	public function test_alt_text_finds_the_image() {

		$job = new Alt_Text();

		// a 1x1 PNG in the Media Library
		$upload = wp_upload_bits( 'ai-pixel.png', null, base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==' ) );
		$id     = wp_insert_attachment( [ 'post_mime_type' => 'image/png', 'post_title' => 'Pixel' ], $upload['file'] );

		$input = $job->validate( [ 'src' => wp_get_attachment_url( $id ), 'context' => 'Spring sale' ] );

		$this->assertNotWPError( $input );
		$this->assertSame( wp_normalize_path( $upload['file'] ), wp_normalize_path( $input['file'] ) );
		$this->assertSame( 'image/png', $input['mime'] );
		$this->assertSame( 'Spring sale', $input['context'] );

		// one the provider fetches itself
		$remote = $job->validate( [ 'src' => 'https://example.com/photo.jpg?size=large' ] );
		$this->assertSame( 'https://example.com/photo.jpg?size=large', $remote['file'] );
		$this->assertSame( 'image/jpeg', $remote['mime'] );

		// one whose URL doesn't say what it is, so its host is asked
		$head = function ( $pre, $args, $url ) {
			return $args['method'] === 'HEAD' && strpos( $url, 'https://example.com/photo?' ) === 0 ? [
				'headers'  => [ 'content-type' => 'image/webp; charset=binary' ],
				'body'     => '',
				'response' => [ 'code' => 200, 'message' => 'OK' ],
				'cookies'  => [],
			] : $pre;
		};

		add_filter( 'pre_http_request', $head, 10, 3 );
		$service = $job->validate( [ 'src' => 'https://example.com/photo?id=42' ] );
		remove_filter( 'pre_http_request', $head, 10 );

		$this->assertNotWPError( $service );
		$this->assertSame( 'image/webp', $service['mime'] );

		$this->assertSame( 'ai_no_image', $job->validate( [] )->get_error_code() );
		$this->assertSame( 'ai_image_type', $job->validate( [ 'src' => 'https://example.com/guide.pdf' ] )->get_error_code() );

		// never a file outside the uploads folder
		$outside = $job->validate( [ 'src' => wp_get_upload_dir()['baseurl'] . '/../../wp-config.php' ] );
		$this->assertWPError( $outside );

		// not even with the dots encoded, which survive esc_url_raw(): it's only ever sent as its address
		$encoded = wp_get_upload_dir()['baseurl'] . '/%2e%2e/%2e%2e/secret.png';
		$this->assertSame( esc_url_raw( $encoded ), $job->validate( [ 'src' => $encoded ] )['file'] );

		// a file in uploads that isn't in the Media Library, like another plugin's private one, isn't read either
		$private = wp_upload_bits( 'private-scan.png', null, base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==' ) );
		$this->assertSame( $private['url'], $job->validate( [ 'src' => $private['url'] ] )['file'] );
		wp_delete_file( $private['file'] );

		// nor an image they couldn't open in the Media Library
		$deny = function ( $caps, $cap ) {
			return $cap === 'read_post' ? [ 'do_not_allow' ] : $caps;
		};
		add_filter( 'map_meta_cap', $deny, 10, 2 );
		$this->assertSame( 'ai_forbidden', $job->validate( [ 'attachment_id' => $id ] )->get_error_code() );
		remove_filter( 'map_meta_cap', $deny, 10 );

		wp_delete_attachment( $id, true );
	}

	public function test_alt_text_cleans_up_the_answer() {

		$job = new Alt_Text();

		$this->assertSame( [ 'alt' => 'A groundhog in a hard hat' ], $job->respond( self::result( 'Alt text: "A groundhog in a hard hat"', 1, 1 ), [] ) );
		$this->assertSame( 'ai_empty', $job->respond( self::result( '  ""  ', 1, 1 ), [] )->get_error_code() );
	}

	public function test_site_health() {

		$health = Plugin::instance()->site_health;

		$this->assertArrayHasKey( 'ai', $health->tests() );

		$this->assertSame( 'good', $health->test_ai()['status'] );

		remove_all_filters( 'groundhogg/ai/can_generate' );
		add_filter( 'groundhogg/ai/can_generate', '__return_false' );

		$this->assertSame( 'recommended', $health->test_ai()['status'] );
		$this->assertArrayHasKey( admin_url( 'options-connectors.php' ), $health->test_ai()['links'] );

		update_option( AI::DISABLED_OPTION, 'on' );

		$this->assertSame( 'good', $health->test_ai()['status'] );
		$this->assertStringContainsString( 'turned off', $health->test_ai()['label'] );
	}

	public function test_the_status_in_one_word() {

		$this->assertSame( 'ready', AI::status() );

		update_option( AI::LIMIT_OPTION, 1 );
		AI::run( 'fake-job', [] );
		$this->assertSame( 'limit_reached', AI::status() );

		remove_all_filters( 'groundhogg/ai/can_generate' );
		add_filter( 'groundhogg/ai/can_generate', '__return_false' );
		$this->assertSame( 'no_provider', AI::status() );

		update_option( AI::DISABLED_OPTION, 'on' );
		$this->assertSame( 'disabled', AI::status() );
	}

	/* ---- the AI page ------------------------------------------------------------------------------------------- */

	/**
	 * The tabs the current user sees, by slug
	 *
	 * @return string[]
	 */
	protected function tabs(): array {
		$page = new AI_Page();

		return array_map( function ( $command ) {
			return wp_parse_args( wp_parse_url( $command['url'], PHP_URL_QUERY ) )['tab'];
		}, $page->command_palette_tab_commands( [] ) );
	}

	public function test_the_ai_page_has_a_tab_for_each_way_in() {

		// administrators see both, and the settings
		$this->assertSame( [ AI_Page::OVERVIEW, Connection::TAB, AI::TAB, AI_Page::USAGE ], $this->tabs() );

		// anyone who works with contacts can connect an assistant of their own, the settings are for administrators
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'sales_rep' ] ) );
		$this->assertSame( [ AI_Page::OVERVIEW, Connection::TAB ], $this->tabs() );

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );
		$this->assertSame( [], $this->tabs() );

		$this->assertStringContainsString( 'page=' . AI::PAGE, Connection::page_url() );
		$this->assertStringContainsString( 'page=' . AI::PAGE, AI::page_url() );
	}

	public function test_the_settings_are_saved_clean() {

		AI::register_settings();

		update_option( AI::DISABLED_OPTION, 'yes please' );
		$this->assertSame( '', get_option( AI::DISABLED_OPTION ) );

		update_option( AI::DISABLED_OPTION, 'on' );
		$this->assertFalse( AI::is_enabled() );

		update_option( AI::LIMIT_OPTION, '-25' );
		$this->assertSame( 25, AI::get_limit() );

		// options.php only saves what's registered in the group the form names
		$this->assertSame( AI::SETTINGS_GROUP, get_registered_settings()[ AI::LIMIT_OPTION ]['group'] );
		$this->assertSame( AI::SETTINGS_GROUP, get_registered_settings()[ AI::DISABLED_OPTION ]['group'] );
	}

	public function test_the_tabs_say_where_things_stand() {

		$page = new AI_Page();

		ob_start();
		$page->overview_view();
		$overview = ob_get_clean();

		$this->assertStringContainsString( 'AI assistant', $overview );
		$this->assertStringContainsString( 'Ready', $overview );
		$this->assertStringContainsString( esc_url( AI::page_url() ), $overview );

		remove_all_filters( 'groundhogg/ai/can_generate' );
		add_filter( 'groundhogg/ai/can_generate', '__return_false' );

		ob_start();
		$page->overview_view();
		$overview = ob_get_clean();

		$this->assertStringContainsString( 'Needs a key', $overview );
		$this->assertStringContainsString( esc_url( admin_url( 'options-connectors.php' ) ), $overview );

		ob_start();
		$page->features_view();
		$features = ob_get_clean();

		$this->assertStringContainsString( 'action="options.php"', $features );
		$this->assertMatchesRegularExpression( '/name=.option_page. value=.' . AI::SETTINGS_GROUP . './', $features );
		$this->assertStringContainsString( 'name="' . AI::LIMIT_OPTION . '"', $features );
		$this->assertStringContainsString( esc_url( Connection::page_url() ), $features, 'the way to the other tab' );
	}

	/* ---- jobs one at a time ------------------------------------------------------------------------------------ */

	public function test_a_job_can_be_turned_off_on_its_own() {

		$off = function ( $on, $name ) {
			return $name === 'fake-job' ? false : $on;
		};

		add_filter( 'groundhogg/ai/job_is_on', $off, 10, 2 );

		$result = AI::run( 'fake-job', [] );

		$this->assertWPError( $result );
		$this->assertSame( 'ai_job_off', $result->get_error_code() );
		$this->assertSame( 0, $this->job->asked, 'nothing is sent' );
		$this->assertSame( 0, get_db( 'ai_usage' )->count() );

		// the browser hides its button, the others stay
		$jobs = AI::client_data()['jobs'];
		$this->assertFalse( $jobs['fake-job'] );
		$this->assertTrue( $jobs['alt-text'] );
		$this->assertTrue( $jobs['subject-lines'] );

		remove_filter( 'groundhogg/ai/job_is_on', $off );

		$this->assertNotWPError( AI::run( 'fake-job', [] ) );
	}

	public function test_the_browser_only_gets_the_jobs_a_person_can_use() {

		// can upload files, can't edit emails
		$user = self::factory()->user->create( [ 'role' => 'author' ] );
		get_userdata( $user )->add_cap( AI::CAP );
		wp_set_current_user( $user );

		$jobs = AI::client_data()['jobs'];

		$this->assertTrue( $jobs['alt-text'] );
		$this->assertFalse( $jobs['subject-lines'] );
	}

	/* ---- subject line ideas ------------------------------------------------------------------------------------ */

	public function test_subject_lines_need_something_to_go_on() {

		$job = AI::get_job( 'subject-lines' );

		$this->assertSame( 'ai_no_content', $job->validate( [ 'content' => '<p>Hi {first}</p>' ] )->get_error_code() );

		$input = $job->validate( [
			'content' => '<h1>Spring sale</h1><p>Everything is 20% off   until Friday.</p>' . str_repeat( 'More words here. ', 1000 ),
			'subject' => '<b>Our spring sale</b>',
			'preview' => 'Ends Friday',
		] );

		$this->assertNotWPError( $input );
		$this->assertStringStartsWith( "Spring sale\nEverything is 20% off until Friday.", $input['content'] );
		$this->assertSame( Groundhogg\AI\Jobs\Subject_Lines::MAX_CONTENT, mb_strlen( $input['content'] ) );
		$this->assertSame( 'Our spring sale', $input['subject'] );
	}

	public function test_subject_lines_are_cleaned_up() {

		$job = AI::get_job( 'subject-lines' );

		// as asked for
		$this->assertSame(
			[ 'ideas' => [ 'Spring sale: 20% off everything', 'Ends Friday: your spring savings' ] ],
			$job->respond( self::result( '{"ideas":["Spring sale: 20% off everything","\"Ends Friday: your spring savings\""," "]}', 1, 1 ), [] )
		);

		// a provider that answers with a list, with doubles and too many
		$list = "1. One\n2) Two\n- Three\n* one\n• Four\nFive\nSix";
		$this->assertSame( [ 'ideas' => [ 'One', 'Two', 'Three', 'Four', 'Five' ] ], $job->respond( self::result( $list, 1, 1 ), [] ) );

		$this->assertSame( 'ai_empty', $job->respond( self::result( '{"ideas":[]}', 1, 1 ), [] )->get_error_code() );
	}

	/**
	 * Do an email ideas job with an answer instead of a provider
	 *
	 * @param string $class  the job
	 * @param string $answer what the provider would have said
	 * @param array  $input
	 *
	 * @return array|\WP_Error
	 */
	protected function run_ideas( string $class, string $answer, array $input ) {

		$job = $this->getMockBuilder( $class )->onlyMethods( [ 'generate' ] )->getMock();
		$job->method( 'generate' )->willReturn( self::result( $answer, 300, 40 ) );

		$name = $job->get_name();

		add_filter( 'groundhogg/ai/jobs', $replace = function ( $jobs ) use ( $job, $name ) {
			return array_merge( array_filter( $jobs, function ( $j ) use ( $name ) {
				return $j->get_name() !== $name;
			} ), [ $job ] );
		}, 20 );
		AI::reset_jobs();

		$result = AI::run( $name, $input );

		remove_filter( 'groundhogg/ai/jobs', $replace, 20 );
		AI::reset_jobs();

		return $result;
	}

	public function test_subject_lines_run_end_to_end() {

		$result = $this->run_ideas( Groundhogg\AI\Jobs\Subject_Lines::class, '{"ideas":["A","B"]}', [ 'content' => 'Our spring sale starts today and runs until Friday.' ] );

		$this->assertNotWPError( $result );
		$this->assertSame( [ 'A', 'B' ], $result['ideas'] );
		$this->assertSame( 340, $result['usage']['tokens'] );
	}

	public function test_preview_text_ideas() {

		$job = AI::get_job( 'preview-text' );

		$this->assertNotNull( $job );
		$this->assertSame( 'edit_emails', $job->get_capability() );
		$this->assertTrue( AI::client_data()['jobs']['preview-text'] );

		$result = $this->run_ideas( Groundhogg\AI\Jobs\Preview_Text::class, '{"ideas":["Everything is 20% off until Friday"]}', [
			'content' => 'Our spring sale starts today and runs until Friday.',
			'subject' => 'Our spring sale',
		] );

		$this->assertNotWPError( $result );
		$this->assertSame( [ 'Everything is 20% off until Friday' ], $result['ideas'] );

		// it's logged as its own feature, for the usage tab
		$this->assertArrayHasKey( 'preview-text', AI::usage_by_job() );
		$this->assertSame( 'ai_empty', $job->respond( self::result( '{"ideas":[]}', 1, 1 ), [] )->get_error_code() );
	}

	/* ---- contact summaries ------------------------------------------------------------------------------------- */

	public function test_a_contact_summary_is_made_from_their_history_without_who_they_are() {

		$job = AI::get_job( 'contact-summary' );

		$this->assertSame( 'ai_no_contact', $job->validate( [ 'contact_id' => 999999999 ] )->get_error_code() );

		$contact = get_contactdata( self::factory()->contacts->create( [ 'email' => 'summary-' . uniqid() . '@example.com', 'first_name' => 'Casey' ] ) );
		$contact->add_tag( 'Summary test tag' );

		$input = $job->validate( [ 'contact_id' => $contact->get_id() ] );

		$this->assertNotWPError( $input );
		$this->assertStringContainsString( 'Summary test tag', $input['markdown'] );
		$this->assertStringNotContainsString( $contact->get_email(), $input['markdown'] );
		$this->assertStringNotContainsString( 'Casey', $input['markdown'] );
		$this->assertNotContains( 'personal', Groundhogg\AI\Jobs\Contact_Summary::sections() );

		// the same check as opening the contact
		$deny = function ( $caps, $cap ) {
			return $cap === 'view_contact' ? [ 'do_not_allow' ] : $caps;
		};
		add_filter( 'map_meta_cap', $deny, 10, 2 );
		$denied = $job->validate( [ 'contact_id' => $contact->get_id() ] );
		remove_filter( 'map_meta_cap', $deny, 10 );

		$this->assertSame( 'ai_forbidden', $denied->get_error_code() );
		$this->assertSame( 403, $denied->get_error_data()['status'] );
	}

	public function test_a_contact_summary_is_cleaned_up() {

		$this->assertFalse( Groundhogg\AI\Jobs\Contact_Summary::clean( 'nope' ) );
		$this->assertFalse( Groundhogg\AI\Jobs\Contact_Summary::clean( [ 'summary' => '' ] ) );

		$clean = Groundhogg\AI\Jobs\Contact_Summary::clean( [
			'temperature' => 'lukewarm',
			'reason'      => '<b>Opens</b> every email',
			'summary'     => 'A long-time customer.',
			'interests'   => [ 'Email', 'Flows', '', 3, 'Reports', 'SMS', 'Forms', 'Too many' ],
			'next_step'   => 'Offer the annual plan.',
		] );

		$this->assertSame( 'cold', $clean['temperature'], 'what it doesn\'t know is cold' );
		$this->assertSame( 'Opens every email', $clean['reason'] );
		$this->assertSame( [ 'Email', 'Flows', 'Reports', 'SMS', 'Forms' ], $clean['interests'] );
	}

	public function test_a_contact_summary_is_kept_on_the_contact() {

		$contact = get_contactdata( self::factory()->contacts->create( [ 'email' => 'summary-' . uniqid() . '@example.com' ] ) );

		$this->assertNull( Groundhogg\AI\Jobs\Contact_Summary::get_saved( $contact ) );

		$result = $this->run_ideas( Groundhogg\AI\Jobs\Contact_Summary::class, wp_json_encode( [
			'temperature' => 'warm',
			'reason'      => 'Clicked twice this week.',
			'summary'     => 'A new lead who is reading about pricing.',
			'interests'   => [ 'Pricing' ],
			'next_step'   => 'Send the pricing guide.',
		] ), [ 'contact_id' => $contact->get_id() ] );

		$this->assertNotWPError( $result );
		$this->assertSame( 'warm', $result['summary']['temperature'] );
		$this->assertSame( wp_get_current_user()->display_name, $result['summary']['by'] );

		$saved = Groundhogg\AI\Jobs\Contact_Summary::get_saved( get_contactdata( $contact->get_id() ) );

		$this->assertSame( 'Send the pricing guide.', $saved['next_step'] );
		$this->assertNotEmpty( $saved['when'] );
	}

	protected function summarize( Contact $contact, string $summary = 'A lead reading about pricing.' ) {
		return $this->run_ideas( Groundhogg\AI\Jobs\Contact_Summary::class, wp_json_encode( [
			'temperature' => 'warm',
			'reason'      => 'Replied this week.',
			'summary'     => $summary,
			'interests'   => [ 'Pricing' ],
			'next_step'   => 'Follow up.',
		] ), [ 'contact_id' => $contact->get_id() ] );
	}

	public function test_a_summary_is_kept_without_who_they_are() {

		$contact = get_contactdata( self::factory()->contacts->create( [ 'email' => 'summary-' . uniqid() . '@example.com', 'first_name' => 'Morgan', 'last_name' => 'Quill' ] ) );

		// a model or an assistant can write it anyway
		$this->summarize( $contact, "Morgan Quill ({$contact->get_email()}) asked about pricing." );

		$saved = Groundhogg\AI\Jobs\Contact_Summary::get_saved( get_contactdata( $contact->get_id() ) );

		$this->assertStringNotContainsString( 'Morgan', $saved['summary'] );
		$this->assertStringNotContainsString( $contact->get_email(), $saved['summary'] );
		$this->assertStringContainsString( 'asked about pricing', $saved['summary'] );
	}

	public function test_a_summary_made_from_what_someone_cant_see_is_hidden_from_them() {

		$contact = get_contactdata( self::factory()->contacts->create( [ 'email' => 'summary-' . uniqid() . '@example.com' ] ) );

		// their messages, which only someone who may see personal details sees
		get_db( 'messages' )->add( [
			'object_id'    => $contact->get_id(),
			'object_type'  => 'contact',
			'direction'    => 'inbound',
			'subject'      => 'Renewal',
			'content'      => 'We will renew if the price stays the same.',
			'date_created' => gmdate( 'Y-m-d H:i:s' ),
		] );

		$this->summarize( $contact, 'Will renew at the same price.' );

		$this->assertSame( 'Will renew at the same price.', Groundhogg\AI\Jobs\Contact_Summary::get_saved( get_contactdata( $contact->get_id() ) )['summary'] );

		// someone who may not see their messages gets that there is one, not what it says
		add_filter( 'groundhogg/pii/is_restricted', '__return_true' );

		$hidden = Groundhogg\AI\Jobs\Contact_Summary::get_saved( get_contactdata( $contact->get_id() ) );
		$this->assertTrue( $hidden['hidden'] );
		$this->assertArrayNotHasKey( 'summary', $hidden );

		// and their own doesn't replace it, it's only shown to them
		$theirs = $this->summarize( get_contactdata( $contact->get_id() ), 'Not much to go on.' );
		$this->assertSame( 'Not much to go on.', $theirs['summary']['summary'] );

		remove_filter( 'groundhogg/pii/is_restricted', '__return_true' );

		$this->assertSame( 'Will renew at the same price.', Groundhogg\AI\Jobs\Contact_Summary::get_saved( get_contactdata( $contact->get_id() ) )['summary'] );
	}

	public function test_a_summary_is_only_kept_by_someone_who_may_change_the_contact() {

		$contact = get_contactdata( self::factory()->contacts->create( [ 'email' => 'summary-' . uniqid() . '@example.com' ] ) );

		$deny = function ( $caps, $cap ) {
			return $cap === 'edit_contact' ? [ 'do_not_allow' ] : $caps;
		};
		add_filter( 'map_meta_cap', $deny, 10, 2 );
		$result = $this->summarize( $contact );
		remove_filter( 'map_meta_cap', $deny, 10 );

		$this->assertNotWPError( $result );
		$this->assertSame( 'warm', $result['summary']['temperature'], 'they still see it' );
		$this->assertNull( Groundhogg\AI\Jobs\Contact_Summary::get_saved( get_contactdata( $contact->get_id() ) ), 'but it isn\'t kept' );
	}

	/* ---- the usage tab ----------------------------------------------------------------------------------------- */

	public function test_the_usage_tab_shows_each_feature_and_request() {

		AI::run( 'fake-job', [] );
		AI::run( 'fake-job', [] );

		$this->job->result = new \WP_Error( 'boom', 'Nope' );
		AI::run( 'fake-job', [] );

		$this->assertSame( [ 'fake-job' => [ 'requests' => 2, 'tokens' => 40, 'failed' => 1 ] ], AI::usage_by_job() );

		ob_start();
		( new AI_Page() )->usage_view();
		$html = ob_get_clean();

		$this->assertStringContainsString( '2 requests, 40 tokens', $html );
		$this->assertStringContainsString( '1 failed', $html );
		$this->assertSame( 2, substr_count( $html, '>Done<' ) );
		$this->assertSame( 1, substr_count( $html, '>Failed<' ) );
		$this->assertStringContainsString( wp_get_current_user()->display_name, $html );
	}

	public function test_links_to_the_old_tabs_still_work() {

		$redirected = function ( string $page, string $tab ) {

			$_GET['page']              = $page;
			$_GET['tab']               = $tab;
			$_SERVER['REQUEST_METHOD'] = 'GET';

			$to    = '';
			$catch = function ( $location ) {
				throw new \RuntimeException( $location );
			};

			add_filter( 'wp_redirect', $catch );

			try {
				( new AI_Page() )->redirect_old_tabs();
			} catch ( \RuntimeException $e ) {
				$to = $e->getMessage();
			}

			remove_filter( 'wp_redirect', $catch );
			unset( $_GET['page'], $_GET['tab'] );

			return $to;
		};

		$this->assertSame( Connection::page_url(), $redirected( 'gh_tools', 'mcp' ) );
		$this->assertSame( AI::page_url(), $redirected( 'gh_settings', 'ai' ) );
		$this->assertSame( '', $redirected( 'gh_tools', 'cron' ) );
	}
}
