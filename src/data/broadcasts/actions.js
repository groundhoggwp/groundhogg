/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { NAMESPACE } from '../constants';
import {getBroadcasts} from "./resolvers";

export function receiveBroadcasts( broadcasts ) {
	return {
		type: TYPES.RECEIVE_BROADCASTS,
		broadcasts,
	};
}

export function setRequestingError( error ) {
	return {
		type: TYPES.SET_REQUESTING_ERROR,
		error
	};
}

export function setUpdatingError( error ) {
	return {
		type: TYPES.SET_UPDATING_ERROR,
		error,
	};
}

export function setIsUpdating( isUpdating ) {
	return {
		type: TYPES.SET_IS_UPDATING,
		isUpdating,
	};
}


export function setIsRequestingBroadcasts( isRequesting ) {
	return {
		type: TYPES.SET_IS_REQUESTING,
		isRequesting,
	};
}

/**
 * Schedules a new Broadcast.
 * Creates row in the broadcast table still needs to run bulk-jobs to enqueue events
 */
export function* scheduleBroadcast(data) {
	yield setIsUpdating( true );
	yield receiveBroadcasts( data );

	try {
		const results = yield apiFetch( {
			path: NAMESPACE + '/broadcasts/schedule/',
			method: 'POST',
			data,
		} );
		yield getBroadcasts(); //refresh broadcast  // todo check is this correct
		yield setIsUpdating( false );
		return { success: true, ...results };
	} catch ( error ) {
		yield setUpdatingError( error );
		return { success: false, ...error };
	}
}

/**
 * Cancels the scheduled Broadcast
 */
export function* cancelBroadcast(data) {
	yield setIsUpdating( true );
	yield receiveBroadcasts( data );

	try {
		const results = yield apiFetch( {
			path: NAMESPACE + '/broadcasts/cancel',
			method: 'POST',
			data,
		} );

		yield setIsUpdating( false );
		return { success: true, ...results };
	} catch ( error ) {
		yield setUpdatingError( error );
		return { success: false, ...error };
	}
}