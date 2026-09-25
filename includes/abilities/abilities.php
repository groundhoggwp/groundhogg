<?php

namespace Groundhogg\Abilities;

use Groundhogg\Abilities\Broadcasts\Cancel_Broadcast;
use Groundhogg\Abilities\Broadcasts\Get_Broadcast;
use Groundhogg\Abilities\Broadcasts\List_Broadcasts;
use Groundhogg\Abilities\Broadcasts\Send_Email_Broadcast;
use Groundhogg\Abilities\Campaigns\Associate_Campaign;
use Groundhogg\Abilities\Campaigns\Create_Campaign;
use Groundhogg\Abilities\Campaigns\List_Campaigns;
use Groundhogg\Abilities\Contacts\Add_Contact_Note;
use Groundhogg\Abilities\Contacts\Add_Custom_Field;
use Groundhogg\Abilities\Contacts\Create_Contact;
use Groundhogg\Abilities\Contacts\Get_Contact;
use Groundhogg\Abilities\Contacts\List_Contact_Notes;
use Groundhogg\Abilities\Contacts\List_Custom_Fields;
use Groundhogg\Abilities\Contacts\List_Owners;
use Groundhogg\Abilities\Contacts\List_Saved_Searches;
use Groundhogg\Abilities\Contacts\Search_Contacts;
use Groundhogg\Abilities\Contacts\Update_Contact;
use Groundhogg\Abilities\Contacts\Update_Custom_Field;
use Groundhogg\Abilities\Db\Describe_Table;
use Groundhogg\Abilities\Db\Query_Table;
use Groundhogg\Abilities\Extensions\Activate_License;
use Groundhogg\Abilities\Extensions\Check_License;
use Groundhogg\Abilities\Extensions\Install_Extension;
use Groundhogg\Abilities\Extensions\List_Extensions;
use Groundhogg\Abilities\Funnels\Activate_Flow;
use Groundhogg\Abilities\Funnels\Add_To_Flow;
use Groundhogg\Abilities\Funnels\Create_Flow;
use Groundhogg\Abilities\Funnels\Deactivate_Flow;
use Groundhogg\Abilities\Funnels\Get_Flow;
use Groundhogg\Abilities\Funnels\List_Flows;
use Groundhogg\Abilities\Funnels\List_Step_Types;
use Groundhogg\Abilities\Funnels\Live_Simulate_Flow;
use Groundhogg\Abilities\Funnels\Simulate_Flow;
use Groundhogg\Abilities\Emails\Create_Email_Template;
use Groundhogg\Abilities\Emails\Get_Email_Template;
use Groundhogg\Abilities\Emails\List_Email_Templates;
use Groundhogg\Abilities\Emails\List_Replacement_Codes;
use Groundhogg\Abilities\Emails\List_Sender_Profiles;
use Groundhogg\Abilities\Emails\Send_Composed_Email;
use Groundhogg\Abilities\Emails\Send_Email_Template;
use Groundhogg\Abilities\Emails\Update_Email_Template;
use Groundhogg\Abilities\Reports\Get_Reports;
use Groundhogg\Abilities\Reports\List_Report_Types;
use Groundhogg\Abilities\Settings\List_Settings;
use Groundhogg\Abilities\Settings\Update_Settings;
use Groundhogg\Abilities\Tags\Create_Tag;
use Groundhogg\Abilities\Tags\List_Tags;
use Groundhogg\Abilities\Utils\Upload_Media;

/**
 * Registers Groundhogg's own abilities/categories, and acts as the registry add-ons use to
 * register their own alongside them - Abilities::add_category() and Abilities::add_ability().
 *
 * WordPress only accepts registrations while the 'wp_abilities_api_categories_init' and
 * 'wp_abilities_api_init' actions are running (they normally fire on 'init'); anywhere else
 * wp_register_ability()/wp_register_ability_category() call _doing_it_wrong() and return null.
 * Groundhogg's own bootstrap runs on 'plugins_loaded' at priority 0 (see Plugin::init()), so any
 * add-on's own 'plugins_loaded' (default priority 10) or 'init' callback is early enough: the
 * call is queued and applied when the action runs. Calling from inside one of those actions
 * registers straight away. Calling after the action has finished is too late - WordPress would
 * refuse it, so it's reported with _doing_it_wrong() instead of being silently lost.
 *
 * Example - a 3rd-party add-on registering its own category and ability:
 *
 *     add_action( 'plugins_loaded', function () {
 *
 *         Abilities::add_category( 'my-addon', [
 *             'label'       => __( 'My Add-on', 'my-addon' ),
 *             'description' => __( 'Abilities provided by My Add-on.', 'my-addon' ),
 *         ] );
 *
 *         Abilities::add_ability( My_Addon\Abilities\Do_The_Thing::class );
 *     } );
 *
 * Do_The_Thing must extend Groundhogg\Abilities\Ability just like Groundhogg's own abilities -
 * it's instantiated the same way, which is what actually calls wp_register_ability().
 */
class Abilities {

	/**
	 * Ability classes registered by add-ons via self::add_ability(), instantiated alongside
	 * Groundhogg's own in register_abilities().
	 *
	 * @var string[]
	 */
	protected static array $extra_abilities = [];

	/**
	 * Ability categories registered by add-ons via self::add_category(), registered alongside
	 * Groundhogg's own in register_categories().
	 *
	 * @var array<string, array>
	 */
	protected static array $extra_categories = [];

	/**
	 * Ability classes / category slugs already handed to WordPress, so a request that is both
	 * queued and made mid-action is never registered twice.
	 *
	 * @var array<string, true>
	 */
	protected static array $registered = [];

	public function __construct() {

		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		add_action(
			'wp_abilities_api_categories_init',
			[ $this, 'register_categories' ]
		);

		add_action(
			'wp_abilities_api_init',
			[ $this, 'register_abilities' ]
		);
	}

	/**
	 * Register an additional ability category, so a 3rd-party add-on's abilities can be grouped
	 * under their own category instead of one of Groundhogg's. See the class docblock for a full
	 * example and the timing requirement.
	 *
	 * @param string $slug the category slug, e.g. "my-addon"
	 * @param array  $args ['label' => string, 'description' => string], same shape
	 *                     wp_register_ability_category() itself takes
	 *
	 * @return void
	 */
	public static function add_category( string $slug, array $args ) {

		if ( ! doing_action( 'wp_abilities_api_categories_init' ) && did_action( 'wp_abilities_api_categories_init' ) ) {
			self::too_late( __METHOD__, 'wp_abilities_api_categories_init' );

			return;
		}

		self::$extra_categories[ $slug ] = $args;

		if ( doing_action( 'wp_abilities_api_categories_init' ) ) {
			self::register_category( $slug, $args );
		}
	}

	/**
	 * Register an additional ability class, instantiated the same way Groundhogg's own abilities
	 * are - which is what actually calls wp_register_ability(). See the class docblock for a full
	 * example and the timing requirement.
	 *
	 * @param string $class fully-qualified class name, extending Groundhogg\Abilities\Ability
	 *
	 * @return void
	 */
	public static function add_ability( string $class ) {

		if ( ! doing_action( 'wp_abilities_api_init' ) && did_action( 'wp_abilities_api_init' ) ) {
			self::too_late( __METHOD__, 'wp_abilities_api_init' );

			return;
		}

		self::$extra_abilities[] = $class;

		if ( doing_action( 'wp_abilities_api_init' ) ) {
			self::instantiate( $class );
		}
	}

	/**
	 * Report a registration that arrived after WordPress stopped accepting them.
	 *
	 * @param string $method the add_*() method the add-on called
	 * @param string $hook   the action that has already finished
	 *
	 * @return void
	 */
	protected static function too_late( string $method, string $hook ) {
		_doing_it_wrong(
			esc_html( $method ),
			/* translators: %s: an action name */
			esc_html( sprintf( __( 'Too late to register: WordPress only accepts registrations while the %s action runs. Call this on plugins_loaded or earlier.', 'groundhogg' ), $hook ) ),
			'4.8.4'
		);
	}

	protected static function register_category( string $slug, array $args ) {

		if ( isset( self::$registered[ 'category:' . $slug ] ) ) {
			return;
		}

		self::$registered[ 'category:' . $slug ] = true;

		wp_register_ability_category( $slug, $args );
	}

	protected static function instantiate( string $class ) {

		if ( isset( self::$registered[ $class ] ) || ! class_exists( $class ) ) {
			return;
		}

		self::$registered[ $class ] = true;

		new $class();
	}

	public function register_categories() {

		self::register_category( 'groundhogg-contacts', [
			'label'       => __( 'Groundhogg Contacts', 'groundhogg' ),
			'description' => __( 'Find, inspect, and manage contacts in Groundhogg.', 'groundhogg' ),
		] );

		self::register_category( 'groundhogg-tags', [
			'label'       => __( 'Groundhogg Tags', 'groundhogg' ),
			'description' => __( 'Find and manage Groundhogg tags.', 'groundhogg' ),
		] );

		self::register_category( 'groundhogg-campaigns', [
			'label'       => __( 'Groundhogg Campaigns', 'groundhogg' ),
			'description' => __( 'Find Groundhogg campaigns, used to group flows, broadcasts, and emails.', 'groundhogg' ),
		] );

		self::register_category( 'groundhogg-funnels', [
			'label'       => __( 'Groundhogg Flows', 'groundhogg' ),
			'description' => __( 'Find Groundhogg flows and add contacts to them.', 'groundhogg' ),
		] );

		self::register_category( 'groundhogg-email', [
			'label'       => __( 'Groundhogg Email', 'groundhogg' ),
			'description' => __( 'Find, inspect, and manage Groundhogg emails.', 'groundhogg' ),
		] );

		self::register_category( 'groundhogg-broadcasts', [
			'label'       => __( 'Groundhogg Broadcasts', 'groundhogg' ),
			'description' => __( 'Schedule Groundhogg email broadcasts and review their performance.', 'groundhogg' ),
		] );

		self::register_category( 'groundhogg-reports', [
			'label'       => __( 'Groundhogg Reports', 'groundhogg' ),
			'description' => __( 'Pull Groundhogg\'s built-in and custom reports.', 'groundhogg' ),
		] );

		self::register_category( 'groundhogg-db', [
			'label'       => __( 'Groundhogg Database', 'groundhogg' ),
			'description' => __( 'Direct, read-only access to Groundhogg\'s own database tables. Administrators only.', 'groundhogg' ),
		] );

		self::register_category( 'groundhogg-utils', [
			'label'       => __( 'Groundhogg Utilities', 'groundhogg' ),
			'description' => __( 'General-purpose utilities that support the other categories but aren\'t specific to any one of them.', 'groundhogg' ),
		] );

		self::register_category( 'groundhogg-extensions', [
			'label'       => __( 'Groundhogg Extensions', 'groundhogg' ),
			'description' => __( 'Manage Groundhogg add-on extensions and their licenses.', 'groundhogg' ),
		] );

		self::register_category( 'groundhogg-settings', [
			'label'       => __( 'Groundhogg Settings', 'groundhogg' ),
			'description' => __( 'List and update Groundhogg settings that have been registered for ability access.', 'groundhogg' ),
		] );

		foreach ( self::$extra_categories as $slug => $args ) {
			self::register_category( $slug, $args );
		}
	}

	public function register_abilities() {

		$abilities = array_merge( [
			Get_Contact::class,
			Create_Contact::class,
			Update_Contact::class,
			Search_Contacts::class,
			List_Custom_Fields::class,
			Add_Custom_Field::class,
			Update_Custom_Field::class,
			List_Saved_Searches::class,
			List_Owners::class,
			List_Tags::class,
			Create_Tag::class,
			List_Campaigns::class,
			Create_Campaign::class,
			Associate_Campaign::class,
			Add_Contact_Note::class,
			List_Contact_Notes::class,
			List_Email_Templates::class,
			Get_Email_Template::class,
			List_Sender_Profiles::class,
			Create_Email_Template::class,
			Update_Email_Template::class,
			List_Replacement_Codes::class,
			Send_Composed_Email::class,
			Send_Email_Template::class,
			Send_Email_Broadcast::class,
			List_Broadcasts::class,
			Get_Broadcast::class,
			Cancel_Broadcast::class,
			List_Flows::class,
			Get_Flow::class,
			List_Step_Types::class,
			Create_Flow::class,
			Activate_Flow::class,
			Deactivate_Flow::class,
			Add_To_Flow::class,
			Simulate_Flow::class,
			Live_Simulate_Flow::class,
			List_Report_Types::class,
			Get_Reports::class,
			Describe_Table::class,
			Query_Table::class,
			Upload_Media::class,
			Activate_License::class,
			Check_License::class,
			List_Extensions::class,
			Install_Extension::class,
			List_Settings::class,
			Update_Settings::class,
		], self::$extra_abilities );

		foreach ( $abilities as $ability ) {
			self::instantiate( $ability );
		}
	}
}
