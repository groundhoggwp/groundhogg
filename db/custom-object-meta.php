<?php

namespace Groundhogg\DB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom Object Meta DB
 *
 * A single shared meta table (gh_object_meta) for every registered custom object
 * type. Rows are keyed by the custom object's ID, which is globally unique across
 * all types because they share one auto-incrementing table, so a single meta
 * table is safe.
 *
 * @since       File available since Release 4.8
 * @subpackage  includes/DB
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Includes
 */
class Custom_Object_Meta extends Meta_DB {

	public function get_db_suffix() {
		return 'gh_object_meta';
	}

	public function get_db_version() {
		return '1.0';
	}

	public function get_object_type() {
		return 'custom_object';
	}

	/**
	 * Meta_DB normally hooks meta cleanup to groundhogg/db/pre_delete/{object_type}.
	 * Custom object rows are deleted through the per-type proxy, which fires
	 * groundhogg/db/pre_delete/{slug} instead, so listen on the generic hook and
	 * route any delete coming from a custom object table through to the regular
	 * cleanup routine.
	 */
	protected function add_additional_actions() {

		// Meta_DB wires up cleanup on groundhogg/db/pre_delete/custom_object and
		// the orphaned meta routine. The specific hook never fires for per-type
		// deletes, so we also listen on the generic pre_delete below.
		parent::add_additional_actions();

		add_action( 'groundhogg/db/pre_delete', [ $this, 'maybe_delete_associated_meta' ], 10, 4 );
	}

	/**
	 * @param string    $object_type  the object type being deleted
	 * @param int|array  $id_or_where  primary key, or a where array
	 * @param array     $formats
	 * @param DB        $table        the table the delete originated from
	 */
	public function maybe_delete_associated_meta( $object_type, $id_or_where, $formats, $table ) {

		if ( ! $table instanceof Custom_Objects ) {
			return;
		}

		$this->delete_associated_meta( $id_or_where, $formats, $table );
	}
}
