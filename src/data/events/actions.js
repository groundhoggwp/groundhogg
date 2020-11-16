/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { NAMESPACE } from '../constants';
import {getEvents} from "./resolvers";

export function receiveEvents( events ) {
	return {
		type: TYPES.RECEIVE_EVENTS,
		events,
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


export function setIsRequestingEvents( isRequesting ) {
	return {
		type: TYPES.SET_IS_REQUESTING,
		isRequesting,
	};
}


/**
 * Cancels the event is in processing
 */
export function* cancelEvent(data) {
	yield setIsUpdating( true );
	yield receiveEvents( data );

	try {
		const results = yield apiFetch( {
			path: NAMESPACE + '/events/cancel',
			method: 'POST',
			data,
		} );

		yield getEvents();
		yield setIsUpdating( false );
		return { success: true, ...results };
	} catch ( error ) {
		yield setUpdatingError( error );
		return { success: false, ...error };
	}
}





/**
 * uncancels the event is in processing
 */
export function* uncancelEvent(data) {
	yield setIsUpdating( true );
	yield receiveEvents( data );

	try {
		const results = yield apiFetch( {
			path: NAMESPACE + '/events/uncancel',
			method: 'POST',
			data,
		} );

		yield getEvents();
		yield setIsUpdating( false );
		return { success: true, ...results };
	} catch ( error ) {
		yield setUpdatingError( error );
		return { success: false, ...error };
	}
}



/**
 * uncancels the event is in processing
 */
export function* runAgain(data) {
	yield setIsUpdating( true );
	yield receiveEvents( data );

	try {
		const results = yield apiFetch( {
			path: NAMESPACE + '/events/run-again',
			method: 'POST',
			data,
		} );

		yield getEvents();
		yield setIsUpdating( false );
		return { success: true, ...results };
	} catch ( error ) {
		yield setUpdatingError( error );
		return { success: false, ...error };
	}
}




