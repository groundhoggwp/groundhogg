<?php

namespace Groundhogg\Abilities;

use Groundhogg\Abilities\Broadcasts\Cancel_Broadcast;
use Groundhogg\Abilities\Broadcasts\Get_Broadcast;
use Groundhogg\Abilities\Broadcasts\List_Broadcasts;
use Groundhogg\Abilities\Broadcasts\Send_Email_Broadcast;
use Groundhogg\Abilities\Campaigns\List_Campaigns;
use Groundhogg\Abilities\Contacts\Add_Contact_Note;
use Groundhogg\Abilities\Contacts\Create_Contact;
use Groundhogg\Abilities\Contacts\Get_Contact;
use Groundhogg\Abilities\Contacts\List_Contact_Notes;
use Groundhogg\Abilities\Contacts\List_Custom_Fields;
use Groundhogg\Abilities\Contacts\List_Owners;
use Groundhogg\Abilities\Contacts\List_Saved_Searches;
use Groundhogg\Abilities\Contacts\Search_Contacts;
use Groundhogg\Abilities\Contacts\Update_Contact;
use Groundhogg\Abilities\Db\Describe_Table;
use Groundhogg\Abilities\Db\Query_Table;
use Groundhogg\Abilities\Funnels\Add_To_Flow;
use Groundhogg\Abilities\Funnels\List_Flows;
use Groundhogg\Abilities\Emails\Create_Email;
use Groundhogg\Abilities\Emails\List_Email_Templates;
use Groundhogg\Abilities\Emails\List_Sender_Profiles;
use Groundhogg\Abilities\Emails\Send_Composed_Email;
use Groundhogg\Abilities\Emails\Send_Email_Template;
use Groundhogg\Abilities\Emails\Update_Email;
use Groundhogg\Abilities\Reports\Get_Reports;
use Groundhogg\Abilities\Reports\List_Report_Types;
use Groundhogg\Abilities\Tags\List_Tags;
use Groundhogg\Abilities\Utils\Upload_Media;

class Abilities {

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
	}

	public function register_abilities() {

		foreach ( [
			Get_Contact::class,
			Create_Contact::class,
			Update_Contact::class,
			Search_Contacts::class,
			List_Custom_Fields::class,
			List_Saved_Searches::class,
			List_Owners::class,
			List_Tags::class,
			List_Campaigns::class,
			Add_Contact_Note::class,
			List_Contact_Notes::class,
			List_Email_Templates::class,
			List_Sender_Profiles::class,
			Create_Email::class,
			Update_Email::class,
			Send_Composed_Email::class,
			Send_Email_Template::class,
			Send_Email_Broadcast::class,
			List_Broadcasts::class,
			Get_Broadcast::class,
			Cancel_Broadcast::class,
			List_Flows::class,
			Add_To_Flow::class,
			List_Report_Types::class,
			Get_Reports::class,
			Describe_Table::class,
			Query_Table::class,
			Upload_Media::class,
		] as $ability ){

			$ability = new $ability();

		}

	}
}
