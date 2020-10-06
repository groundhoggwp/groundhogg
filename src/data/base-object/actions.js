/**
 * Internal dependencies
 */
import TYPES from './action-types';

export function createItems( items ) {
	return {
		type: TYPES.CREATE_ITEMS,
		items,
	};
}

export function createItem( item ) {
	return {
		type: TYPES.CREATE_ITEM,
		item,
	};
}

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

export function updateItems( items ) {
	return {
		type: TYPES.UPDATE_ITEMS,
		items,
	};
}

export function updateItem( item ) {
	return {
		type: TYPES.UPDATE_ITEM,
		item,
	};
}

export function deleteItems( itemIds ) {
	return {
		type: TYPES.DELETE_ITEMS,
		itemIds,
	};
}

export function deleteItem( itemId ) {
	return {
		type: TYPES.DELETE_ITEM,
		itemId,
	};
}

export function setIsCreatingItems( isCreating ) {
	return {
		type: TYPES.SET_IS_CREATING,
		isCreating,
	};
}

export function setCreatingError( error ) {
	return {
		type: TYPES.SET_CREATING_ERROR,
		error
	};
}

export function setIsRequestingItems( isRequesting ) {
	return {
		type: TYPES.SET_IS_REQUESTING,
		isRequesting,
	};
}

export function setRequestingError( error ) {
	return {
		type: TYPES.SET_REQUESTING_ERROR,
		error
	};
}

export function setIsUpdatingItems( isUpdating ) {
	return {
		type: TYPES.SET_IS_UPDATING,
		isUpdating,
	};
}

export function setUpdatingError( error ) {
	return {
		type: TYPES.SET_UPDATING_ERROR,
		error,
	};
}

export function setIsDeletingItems( isDeleting ) {
	return {
		type: TYPES.SET_IS_DELETING,
		isDeleting,
	};
}

export function setDeletingError( error ) {
	return {
		type: TYPES.SET_DELETING_ERROR,
		error,
	};
}

/**
 * This is overridden
 */
export function getEndpoint() {}