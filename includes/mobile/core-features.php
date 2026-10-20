<?php

namespace Groundhogg\Mobile;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core's own mobile feature. It registers through the same public registry add-ons use, so anything
 * core can do, an add-on can do. The components for these ids ship in the core bundle.
 */
class Core_Features {

	/**
	 * @param Features $features
	 */
	public static function register( Features $features ) {

		$features->register( 'core', [
			'label'            => __( 'Contacts and tasks', 'groundhogg' ),
			// every Groundhogg role has this, anyone without it has no business in the app
			'cap'              => 'view_contacts',
			'nav'              => [
				[
					'id'        => 'home',
					'label'     => __( 'Home', 'groundhogg' ),
					'icon'      => 'home',
					'route'     => '/',
					'placement' => 'tab',
					'priority'  => 1,
				],
				[
					'id'        => 'contacts',
					'label'     => __( 'Contacts', 'groundhogg' ),
					'icon'      => 'contacts',
					'route'     => '/contacts',
					'placement' => 'tab',
					'priority'  => 10,
					'cap'       => 'view_contacts',
				],
				[
					'id'        => 'tasks',
					'label'     => __( 'Tasks', 'groundhogg' ),
					'icon'      => 'tasks',
					'route'     => '/tasks',
					'placement' => 'tab',
					'priority'  => 20,
					'cap'       => 'view_tasks',
				],
			],
			'home_cards'       => [
				[
					'id'       => 'my-tasks',
					'label'    => __( 'My tasks', 'groundhogg' ),
					'priority' => 10,
					'cap'      => 'view_tasks',
				],
				[
					'id'       => 'recently-viewed',
					'label'    => __( 'Recently viewed', 'groundhogg' ),
					'priority' => 15,
					'cap'      => 'view_contacts',
				],
				[
					'id'       => 'recent-contacts',
					'label'    => __( 'Recently added contacts', 'groundhogg' ),
					'priority' => 20,
					'cap'      => 'view_contacts',
				],
			],
			'contact_actions'  => [
				[
					'id'       => 'call',
					'label'    => __( 'Call', 'groundhogg' ),
					'icon'     => 'phone',
					'href'     => 'tel:{mobile_phone|primary_phone}',
					'priority' => 10,
				],
				[
					'id'       => 'text',
					'label'    => __( 'Text', 'groundhogg' ),
					'icon'     => 'message',
					'href'     => 'sms:{mobile_phone|primary_phone}',
					'priority' => 20,
				],
				[
					// no href: the app opens a sheet to choose between the phone's email app, writing one here, or a saved email
					'id'       => 'email',
					'label'    => __( 'Email', 'groundhogg' ),
					'icon'     => 'mail',
					'priority' => 30,
				],
				[
					// a handler too: it opens a sheet to pick an internal form and fill it out for this contact
					'id'       => 'form',
					'label'    => __( 'Form', 'groundhogg' ),
					'icon'     => 'form',
					'priority' => 40,
					// the forms endpoints need this, since internal forms are also how contacts are added
					'cap'      => 'add_contacts',
				],
			],
			'contact_panels'   => [
				[
					'id'       => 'notes',
					'label'    => __( 'Notes', 'groundhogg' ),
					'icon'     => 'note',
					'priority' => 10,
					'cap'      => 'view_notes',
				],
				[
					'id'       => 'tasks',
					'label'    => __( 'Tasks', 'groundhogg' ),
					'icon'     => 'tasks',
					'priority' => 20,
					'cap'      => 'view_tasks',
				],
				[
					'id'       => 'activity',
					'label'    => __( 'Activity', 'groundhogg' ),
					'icon'     => 'globe',
					'priority' => 30,
					'cap'      => 'view_contacts',
				],
			],
			'quick_create'     => [
				[
					'id'       => 'new-contact',
					'label'    => __( 'New contact', 'groundhogg' ),
					'icon'     => 'contacts',
					'priority' => 10,
					'cap'      => 'add_contacts',
				],
				[
					'id'       => 'new-task',
					'label'    => __( 'New task', 'groundhogg' ),
					'icon'     => 'tasks',
					'priority' => 20,
					'cap'      => 'add_tasks',
				],
			],
		] );
	}
}
