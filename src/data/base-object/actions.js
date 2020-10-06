/**
 * Internal dependencies
 */
import TYPES from './action-types';

export function receiveItems( items ) {
	return {
		type: TYPES.RECEIVE_ITEMS,
		items,
	};
}

export function receiveItem( item ) {
	return {
		type: TYPES.RECEIVE_ITEM,
		item,
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

export function setIsRequestingItems( isRequesting ) {
	return {
		type: TYPES.SET_IS_REQUESTING,
		isRequesting,
	};
}