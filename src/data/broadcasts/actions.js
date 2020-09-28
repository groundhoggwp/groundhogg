/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { NAMESPACE } from '../constants';

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

/**
 * Update Broadcast Not required as there is nothing to update once set but you can cancel it and that will be handled in the row actions
 */
export function* updateBroadcast( data ) {
	yield setIsUpdating( true );
	yield receiveBroadcasts( data );

	try {
		const results = yield apiFetch( {
			path: NAMESPACE + '/tags',
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