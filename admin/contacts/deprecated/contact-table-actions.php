<?php

namespace Groundhogg\Admin\Contacts\Tables;

use Groundhogg\Contact;
use Groundhogg\Plugin;
use Groundhogg\Tag;
use function Groundhogg\admin_page_url;
use function Groundhogg\dashicon_e;
use function Groundhogg\get_array_var;
use function Groundhogg\get_post_var;
use function Groundhogg\html;
use function Groundhogg\scheduled_time_column;

class Contact_Table_Actions {

	public function __construct() {
		if ( did_action( 'admin_init' ) ) {
			$this->register_core_actions();
		} else {
			add_action( 'admin_init', [ $this, 'register_core_actions' ] );
		}
	}

	/**
	 * Static memory of the meta boxes
	 *
	 * @var array[]
	 */
	public static $actions = [];

	/**
	 * Register a new contact table column
	 *
	 * @param string   $id         the ID of the info card
	 * @param string   $name
	 * @param string   $name_plural
	 * @param callable $callback
	 * @param string   $capability the minimum capability for the viewing user to see the data in this card.
	 */
	public static function register( string $id, string $name, string $name_plural, callable $callback, $capability = 'view_contacts' ) {

		if ( empty( $id ) || ! is_callable( $callback ) ) {
			return;
		}

		self::$actions[ sanitize_key( $id ) ] = [
			'id'          => sanitize_key( $id ),
			'name'        => $name,
			'name_plural' => $name_plural,
			'callback'    => $callback,
			'capability'  => $capability,
		];
	}

	/**
	 * Output the column
	 *
	 * @param $query
	 * @param $total_contacts
	 * @param $table
	 */
	public static function do_contact_actions( $query, $total_contacts, $table ) {

		// no contacts to show
		if ( $total_contacts === 0 ) {
			return;
		}

		$options = [];

		foreach ( self::$actions as $action ) {
			extract( $action );

			/**
			 * @var int      $id
			 * @var string   $name
			 * @var string   $name_plural
			 * @var callable $callback
			 * @var string   $capability
			 */

			if ( ! current_user_can( $capability ) ) {
				continue;
			}

			$options[ $id ] = html()->e( 'a', [
				'href' => call_user_func( $callback, $query, $total_contacts, $table )
			], sprintf( _n( $name, $name_plural, $total_contacts ), $total_contacts ) );

		}

		echo html()->dropdown_button( __( 'More Actions', 'groundhogg' ), $options );
	}

	/**
	 * Register the core cards
	 */
	public function register_core_actions() {

		self::register( 'bulk-edit',
			_x( 'Edit %d contact', 'table-actions', 'groundhogg' ),
			_x( 'Edit %d contacts', 'table-actions', 'groundhogg' ),
			[ $this, 'bulk_edit_callback' ],
			'edit_contacts'
		);

		self::register( 'export',
			_x( 'Export %d contact', 'table-actions', 'groundhogg' ),
			_x( 'Export %d contacts', 'table-actions', 'groundhogg' ),
			[ $this, 'export_callback' ],
			'export_contacts'
		);

		self::register( 'broadcast',
			_x( 'Send a broadcast to %d contact', 'table-actions', 'groundhogg' ),
			_x( 'Send a broadcast to %d contacts', 'table-actions', 'groundhogg' ),
			[ $this, 'broadcast_callback' ],
			'schedule_broadcasts'
		);

		self::register( 'funnel',
			_x( 'Add %d contact to a funnel', 'table-actions', 'groundhogg' ),
			_x( 'Add %d contacts to a funnel', 'table-actions', 'groundhogg' ),
			[ $this, 'funnel_callback' ],
			'edit_funnels'
		);

		self::register( 'delete',
			_x( 'Delete  %d contact', 'table-actions', 'groundhogg' ),
			_x( 'Delete %d contacts', 'table-actions', 'groundhogg' ),
			[ $this, 'delete_callback' ],
			'delete_contacts'
		);

		do_action( 'groundhogg/admin/contacts/register_table_actions', $this );
	}

	# =============== COLUMN CALLBACKS FOR CORE ACTIONS =============== #

	/**
	 * @param $query          array
	 * @param $total_contacts int
	 * @param $table          Contacts_Table
	 *
	 * @return string
	 */
	public function export_callback( $query, $total_contacts, $table ) {

		unset( $query['number'] );
		unset( $query['limit'] );
		unset( $query['offset'] );

		return admin_page_url( 'gh_tools', [
			'tab'    => 'export',
			'action' => 'choose_columns',
			'query'  => $query,
		] );
	}

	/**
	 * @param $query
	 * @param $total_contacts
	 * @param $table
	 *
	 * @return string
	 */
	public function broadcast_callback( $query, $total_contacts, $table ) {

		unset( $query['number'] );
		unset( $query['limit'] );
		unset( $query['offset'] );

		return admin_page_url( 'gh_broadcasts', [
			'action' => 'add',
			'type'   => 'email',
			'query'  => $query,
		] );
	}


	/**
	 * @param $query
	 * @param $total_contacts
	 * @param $table
	 *
	 * @return string
	 */
	public function delete_callback( $query, $total_contacts, $table ) {

		unset( $query['number'] );
		unset( $query['limit'] );
		unset( $query['offset'] );

		return admin_page_url( 'gh_tools', [
			'tab'   => 'delete',
			'query' => $query,
		] );
	}

	/**
	 * @param $query
	 *
	 * @return string
	 */
	public function funnel_callback( $query ) {

		unset( $query['number'] );
		unset( $query['limit'] );
		unset( $query['offset'] );

		return admin_page_url( 'gh_funnels', [
			'action' => 'add_to_funnel',
			'query'  => $query,
		] );
	}

	/**
	 * @param $query
	 *
	 * @return string
	 */
	public function bulk_edit_callback( $query ) {

		unset( $query['number'] );
		unset( $query['limit'] );
		unset( $query['offset'] );

		return admin_page_url( 'gh_contacts', [
			'action' => 'bulk_edit',
			'query'  => $query,
		] );
	}
}
