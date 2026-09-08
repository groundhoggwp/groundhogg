<?php

namespace Groundhogg\Abilities;

use Groundhogg\Abilities\Contacts\Add_Contact_Note;
use Groundhogg\Abilities\Contacts\Create_Contact;
use Groundhogg\Abilities\Contacts\Get_Contact;
use Groundhogg\Abilities\Contacts\List_Contact_Notes;
use Groundhogg\Abilities\Contacts\List_Custom_Fields;
use Groundhogg\Abilities\Contacts\List_Owners;
use Groundhogg\Abilities\Contacts\List_Saved_Searches;
use Groundhogg\Abilities\Contacts\Search_Contacts;
use Groundhogg\Abilities\Contacts\Update_Contact;
use Groundhogg\Abilities\Tags\List_Tags;

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

		wp_register_ability_category( 'groundhogg-funnels', [
			'label'       => __( 'Groundhogg Funnels', 'groundhogg' ),
			'description' => __( 'Find, inspect, and manage Groundhogg funnels.', 'groundhogg' ),
		] );

		wp_register_ability_category( 'groundhogg-email', [
			'label'       => __( 'Groundhogg Email', 'groundhogg' ),
			'description' => __( 'Find, inspect, and manage Groundhogg emails.', 'groundhogg' ),
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
			Add_Contact_Note::class,
			List_Contact_Notes::class,
		] as $ability ){

			$ability = new $ability();

		}

	}
}
