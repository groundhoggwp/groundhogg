/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { NAMESPACE } from '../constants';

export function receiveTags( tags ) {
	return {
		type: TYPES.RECEIVE_TAGS,
		tags,
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

export function* updateTags( data ) {
	yield setIsUpdating( true );
	yield receiveTags( data );

	try {
		const results = yield apiFetch( {
			path: NAMESPACE + '/tags',
			method: 'POST',
			data,
		} );

		yield setIsUpdating( false );
		return { results };
	} catch ( error ) {
		yield setUpdatingError( error );
		return { success: false, ...error };
	}
}
