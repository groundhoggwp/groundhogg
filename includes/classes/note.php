<?php

namespace Groundhogg\Classes;

use Groundhogg\Base_Object;
use function Groundhogg\create_object_from_type;
use function Groundhogg\do_replacements;
use function Groundhogg\get_db;

class Note extends Base_Object {

	protected function post_setup() {
		// TODO: Implement post_setup() method.
	}

	protected function get_db() {
		return get_db( 'notes' );
	}

	public function get_owner_id() {
		return absint( $this->user_id );
	}

	protected function sanitize_columns( $data = [] ) {
		foreach ( $data as $col => &$val ) {
			switch ( $col ) {
				case 'content':
					$val = wp_kses_post( $val );
					break;
			}
		}

		return $data;
	}

	public function create( $data = [] ) {

		$args = wp_parse_args( $data, [
			'object_type' => '',
			'object_id'   => '',
			'content'     => '',
		] );

		// process replacements
		if ( $args['object_type'] && $args['object_id'] && ! empty( $args['content'] ) ) {
			// can't use Note::get_associated_object() because the note has not yet been created
			// we're updating the content with replacements before it's been saved in the DB
			$object  = create_object_from_type( $args['object_id'], $args['object_type'] );
			$content = $args['content'];

			if ( $object ) {
				switch ( $data['object_type'] ) {
					case 'contact':
						$content = do_replacements( $content, $object->ID );
						break;
					case 'deal':

						if ( $object->contact_id ){ // in the future we'll use the contact_id as the "primary contact" like with companies
							$content = do_replacements( $content, absint( $object->contact_id ) );
						} else {
							$contacts = $object->get_contacts();
							if ( ! empty( $contacts ) ) {
								$content = do_replacements( $content, $contacts[0] );
							}
						}
						break;
					case 'company':
						$content = do_replacements( $content, $object->primary_contact_id );
						break;
				}
			}

			/**
			 * Filter the content for specific object types. For example handling replacements fpr non-contact objects.
			 *
			 * @param string      $content
			 * @param int         $object_id
			 * @param string      $object_type
			 * @param Base_Object $object
			 * @param array       $data
			 */
			$data['content'] = apply_filters( 'groundhogg/note/content', $content, $args['object_id'], $args['object_type'], $object, $data );
		}

		return parent::create( $data );
	}

	/**
	 * Gets the related object
	 *
	 * @return \Groundhogg\DB_Object|\Groundhogg\DB_Object_With_Meta
	 */
	public function get_associated_object() {
		return create_object_from_type( $this->object_id, $this->object_type );
	}

	public function get_as_array() {
		return array_merge( parent::get_as_array(), [
			'i18n' => [
				'time_diff' => human_time_diff( $this->timestamp, time() )
			]
		] );
	}
}
