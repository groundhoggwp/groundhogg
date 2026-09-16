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
 * Both must be called before the WP Abilities API actually registers anything - the
 * 'wp_abilities_api_categories_init'/'wp_abilities_api_init' hooks below, which normally fire on
 * 'init'. Groundhogg's own bootstrap runs on 'plugins_loaded' at priority 0 (see Plugin::init()),
 * so any add-on's own 'plugins_loaded' (default priority 10) or 'init' callback is early enough.
 * Registering from that far out just queues the category/class; if a call ever comes in after the
 * relevant hook has already fired, it's applied immediately instead of being silently dropped.
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

		self::$extra_categories[ $slug ] = $args;

		// The categories hook already fired - register it right away rather than dropping it.
		if ( did_action( 'wp_abilities_api_categories_init' ) && function_exists( 'wp_register_ability_category' ) ) {
			wp_register_ability_category( $slug, $args );
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

		self::$extra_abilities[] = $class;

		// The abilities hook already fired - register it right away rather than dropping it.
		if ( did_action( 'wp_abilities_api_init' ) ) {
			new $class();
		}
	}

	public function register_categories() {

		wp_register_ability_category( 'groundhogg-contacts', [
			'label'       => __( 'Groundhogg Contacts', 'groundhogg' ),
			'description' => __( 'Find, inspect, and manage contacts in Groundhogg.', 'groundhogg' ),
		] );

		wp_register_ability_category( 'groundhogg-tags', [
			'label'       => __( 'Groundhogg Tags', 'groundhogg' ),
			'description' => __( 'Find and manage Groundhogg tags.', 'groundhogg' ),
		] );

		wp_register_ability_category( 'groundhogg-campaigns', [
			'label'       => __( 'Groundhogg Campaigns', 'groundhogg' ),
			'description' => __( 'Find Groundhogg campaigns, used to group flows, broadcasts, and emails.', 'groundhogg' ),
		] );

		wp_register_ability_category( 'groundhogg-funnels', [
			'label'       => __( 'Groundhogg Flows', 'groundhogg' ),
			'description' => __( 'Find Groundhogg flows and add contacts to them.', 'groundhogg' ),
		] );

		wp_register_ability_category( 'groundhogg-email', [
			'label'       => __( 'Groundhogg Email', 'groundhogg' ),
			'description' => __( 'Find, inspect, and manage Groundhogg emails.', 'groundhogg' ),
		] );

		wp_register_ability_category( 'groundhogg-broadcasts', [
			'label'       => __( 'Groundhogg Broadcasts', 'groundhogg' ),
			'description' => __( 'Schedule Groundhogg email broadcasts and review their performance.', 'groundhogg' ),
		] );

		wp_register_ability_category( 'groundhogg-reports', [
			'label'       => __( 'Groundhogg Reports', 'groundhogg' ),
			'description' => __( 'Pull Groundhogg\'s built-in and custom reports.', 'groundhogg' ),
		] );

		wp_register_ability_category( 'groundhogg-db', [
			'label'       => __( 'Groundhogg Database', 'groundhogg' ),
			'description' => __( 'Direct, read-only access to Groundhogg\'s own database tables. Administrators only.', 'groundhogg' ),
		] );

		wp_register_ability_category( 'groundhogg-utils', [
			'label'       => __( 'Groundhogg Utilities', 'groundhogg' ),
			'description' => __( 'General-purpose utilities that support the other categories but aren\'t specific to any one of them.', 'groundhogg' ),
		] );

		wp_register_ability_category( 'groundhogg-extensions', [
			'label'       => __( 'Groundhogg Extensions', 'groundhogg' ),
			'description' => __( 'Manage Groundhogg add-on extensions and their licenses.', 'groundhogg' ),
		] );

		wp_register_ability_category( 'groundhogg-settings', [
			'label'       => __( 'Groundhogg Settings', 'groundhogg' ),
			'description' => __( 'List and update Groundhogg settings that have been registered for ability access.', 'groundhogg' ),
		] );

		foreach ( self::$extra_categories as $slug => $args ) {
			wp_register_ability_category( $slug, $args );
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
			new $ability();
		}
	}
}
