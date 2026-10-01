<?php

namespace Groundhogg;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Helper functions for handling edit locking among any base_object UI.
 * based on the edit lock system in WP core post.php wp_set_edit_lock()
 */

/**
 * Show the "User is currently editing" message above the row title in tables
 *
 * @param Base_Object_With_Meta $object
 *
 * @return void
 */
function row_item_locked_text( Base_Object_With_Meta $object ) {

	$lock_holder = check_lock( $object );

	if ( $lock_holder ) {

		$user          = get_userdata( $lock_holder );
		$locked_avatar = get_avatar( $user->ID, 18 );
		/* translators: %s: User's display name. */
		$locked_text = esc_html( sprintf( __( '%s is currently editing', 'groundhogg' ), $user->display_name ) );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above
		echo '<div class="locked-info"><span class="locked-avatar">' . $locked_avatar . '</span> <span class="locked-text">' . $locked_text . "</span></div>\n";
	}
}

/**
 * Enqueues the edit lock script
 *
 * @param Base_Object_With_Meta $object        the object we're locking
 * @param bool                  $can_take_over whether to show the takeover button
 *
 * @return void
 */
function use_edit_lock( Base_Object_With_Meta $object, $can_take_over = true ) {

	if ( get_url_var( 'take-over' ) == 1 && $can_take_over ){
		check_admin_referer( 'take-over' );
		set_lock( $object );
		wp_safe_redirect( $object->admin_link() );
		die();
	}

	$lock_data = [
		'type' => $object->_get_object_type(),
		'id'   => $object->ID,
		'user' => get_current_user_id(),
		'exit' => admin_page_url( get_url_var( 'page' ) ),
	];

	$locked = check_lock( $object );

	if ( $locked ) {
		// object is being edited by another user

		$user = get_userdata( $locked );

		$error = [
			'name'          => $user->display_name,
			/* translators: %s: User's display name. */
			'text' => esc_html( sprintf( __( '%s is currently editing.', 'groundhogg' ), $user->display_name ) ),
			'avatar_src'    => get_avatar_url( $user->ID, array( 'size' => 64 ) ),
			'avatar_src_2x' => get_avatar_url( $user->ID, array( 'size' => 128 ) ),
		];

		if ( $can_take_over ) {
			$error['take_over'] = add_query_arg( [
				'take-over' => 1,
				'_wpnonce'  => wp_create_nonce( 'take-over' )
			], get_request_uri() );
		}

		// Send the lock error
		$lock_data['lock_error'] = $error;
	} else {
		// Otherwise set the lock
		$new_lock          = set_lock( $object );
		$lock_data['lock'] = implode( ':', $new_lock );
		// to release the lock when the editor closes
		$lock_data['remove_nonce'] = wp_create_nonce( 'groundhogg_remove_lock' );
		// to tell when something else changes it, like an ability, see maybe_refresh_lock()
		$lock_data['version']      = get_edit_version( $object );
		$lock_data['changed_text'] = get_changed_text( $object );
	}

	wp_enqueue_script( 'groundhogg-admin-edit-lock' );

	wp_add_inline_script( 'groundhogg-admin-edit-lock', 'var GhLockData = ' . wp_json_encode( $lock_data ) );
}

/**
 * Set the object lock
 *
 * @param Base_Object_With_Meta $object
 *
 * @return array|false
 */
function set_lock( Base_Object_With_Meta $object ) {

	$user_id = get_current_user_id();

	if ( 0 == $user_id ) {
		return false;
	}

	$now  = time();
	$lock = "$now:$user_id";

	$object->update_meta( '_edit_lock', $lock );

	do_action( 'groundhogg/set_lock', $object );

	return array( $now, $user_id );
}

/**
 * Determines whether the object is currently being edited by another user.
 *
 * @since 2.5.0
 *
 * @param Base_Object_With_Meta $object The object to check if it's locked
 *
 * @return int|false ID of the user with lock. False if the object does not exist, object is not locked,
 *                   the user with lock does not exist, or the object is locked by current user.
 */
function check_lock( Base_Object_With_Meta $object ) {

	$lock = $object->get_meta( '_edit_lock', true );

	if ( ! $lock ) {
		return false;
	}

	$lock = explode( ':', $lock );
	$time = $lock[0];
	$user = $lock[1] ?? $object->get_meta( '_edit_last', true );

	if ( ! get_userdata( $user ) ) {
		return false;
	}

	/** This filter is documented in wp-admin/includes/ajax-actions.php */
	$time_window = apply_filters( 'wp_check_post_lock_window', 150 );

	if ( $time && $time > time() - $time_window && get_current_user_id() != $user ) {
		return $user;
	}

	return false;
}

add_filter( 'heartbeat_received', __NAMESPACE__ . '\maybe_refresh_lock', 10, 3 );

/**
 * Refresh the edit lock of an object, maybe
 *
 * @param array $response
 * @param array $data
 * @param       $screen_id
 *
 * @return array
 */
function maybe_refresh_lock( array $response, array $data, $screen_id ) {

	// Not editing anything
	if ( ! isset_not_empty( $data, 'groundhogg-refresh-lock' ) ) {
		return $response;
	}

	$send     = [];
	$received = $data['groundhogg-refresh-lock'];
	$id       = absint( $received['id'] );
	$type     = sanitize_key( $received['type'] );

	if ( ! $id || ! $type ) {
		return $response;
	}

	if ( ! current_user_can( "edit_$type", $id ) ) {
		return $response;
	}

	$object  = create_object_from_type( $id, $type );
	$user_id = check_lock( $object );
	$user    = get_userdata( $user_id );

	if ( $user ) {

		$error = [
			'name'          => $user->display_name,
			/* translators: %s: User's display name. */
			'text' => esc_html( sprintf( __( '%s is currently editing.', 'groundhogg' ), $user->display_name ) ),
			'avatar_src'    => get_avatar_url( $user->ID, array( 'size' => 64 ) ),
			'avatar_src_2x' => get_avatar_url( $user->ID, array( 'size' => 128 ) ),
		];

		$send['lock_error'] = $error;
	} else {
		$new_lock = set_lock( $object );

		if ( $new_lock ) {
			$send['new_lock'] = implode( ':', $new_lock );
		}

		// the editor compares it with the version it has, see edit-lock.js
		if ( isset( $received['version'] ) ) {
			$send['version'] = get_edit_version( $object );
		}
	}

	$response['groundhogg-refresh-lock'] = $send;

	return $response;
}

/**
 * A version of the object, which changes when it's changed, so an editor can tell when something else changed it
 * while it's open, like an ability. Meta starting with _ (like the edit lock) doesn't count.
 *
 * @param Base_Object_With_Meta $object
 *
 * @return string
 */
function get_edit_version( Base_Object_With_Meta $object ) {

	$meta = array_filter( $object->get_meta(), function ( $key ) {
		return ! str_starts_with( (string) $key, '_' );
	}, ARRAY_FILTER_USE_KEY );

	ksort( $meta );

	$version = md5( wp_json_encode( [ $object->get_data(), $meta ] ) );

	/**
	 * The version of an object being edited, see get_edit_version()
	 *
	 * @param string                $version
	 * @param Base_Object_With_Meta $object
	 */
	return (string) apply_filters( "groundhogg/edit_lock/{$object->_get_object_type()}/version", $version, $object );
}

/**
 * What the editor says when the object was changed while it's open
 *
 * @param Base_Object_With_Meta $object
 *
 * @return string
 */
function get_changed_text( Base_Object_With_Meta $object ) {

	switch ( $object->_get_object_type() ) {
		case 'funnel':
			$text = __( 'Changes have been made to this flow. Reload the page to see them.', 'groundhogg' );
			break;
		case 'email':
			$text = __( 'Changes have been made to this email. Reload the page to see them.', 'groundhogg' );
			break;
		default:
			$text = __( 'Changes have been made since this page was opened. Reload the page to see them.', 'groundhogg' );
			break;
	}

	/**
	 * What the editor says when the object was changed while it's open
	 *
	 * @param string                $text
	 * @param Base_Object_With_Meta $object
	 */
	return esc_html( apply_filters( "groundhogg/edit_lock/{$object->_get_object_type()}/changed_text", $text, $object ) );
}

add_filter( 'groundhogg/edit_lock/funnel/version', __NAMESPACE__ . '\funnel_edit_version', 10, 2 );

/**
 * A flow's version is its draft's revision, which the flow editor keeps up to date with its own saves
 *
 * @param string                $version
 * @param Base_Object_With_Meta $object the funnel, which isn't a Funnel when it's from create_object_from_type()
 *
 * @return string
 */
function funnel_edit_version( $version, $object ) {

	$funnel = $object instanceof Funnel ? $object : new Funnel( $object->get_id() );

	return $funnel->while_editing( function () use ( $funnel ) {
		return Abilities\Funnels\Get_Flow::revision( $funnel );
	} );
}

add_action( 'wp_ajax_groundhogg_remove_lock', __NAMESPACE__ . '\ajax_remove_lock' );

/**
 * Release the lock when the editor closes, so others don't have to wait for it to expire
 *
 * @return void
 */
function ajax_remove_lock() {

	$id   = absint( get_post_var( 'id' ) );
	$type = sanitize_key( get_post_var( 'type' ) );

	if ( ! $id || ! $type || ! wp_verify_nonce( get_post_var( '_wpnonce' ), 'groundhogg_remove_lock' ) || ! current_user_can( "edit_$type", $id ) ) {
		wp_send_json_error();
	}

	$object = create_object_from_type( $id, $type );

	if ( ! $object || ! $object->exists() ) {
		wp_send_json_error();
	}

	$lock = explode( ':', (string) $object->get_meta( '_edit_lock' ) );

	// only the user holding the lock can release it
	if ( absint( $lock[1] ?? 0 ) !== get_current_user_id() ) {
		wp_send_json_error();
	}

	$object->delete_meta( '_edit_lock' );

	wp_send_json_success();
}

add_action( 'groundhogg/set_lock', __NAMESPACE__ . '\lock_emails_when_editing_funnel' );

/**
 * Sets a lock for email assets within a funnel whenever the lock for a funnel is set
 *
 * @param Base_Object_With_Meta $object
 *
 * @return void
 */
function lock_emails_when_editing_funnel( Base_Object_With_Meta $object ){

	if ( ! is_a( $object, Funnel::class ) ){
		return;
	}

	$emails = $object->get_emails();

	foreach ( $emails as $email ){
		set_lock( $email );
	}
}
