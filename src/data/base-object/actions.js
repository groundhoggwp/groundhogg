/**
 * Internal dependencies
 */
import TYPES from './action-types';

export default (endpoint) => ( {

	endpoint,

	createItems( items ) {
		return {
			type: TYPES.CREATE_ITEMS,
			items,
		};
	},

	createItem( item ) {
		return {
			type: TYPES.CREATE_ITEM,
			item,
		};
	},

	receiveItems( items ) {
		return {
			type: TYPES.RECEIVE_ITEMS,
			items,
		};
	},

	receiveItem( item ) {
		return {
			type: TYPES.RECEIVE_ITEM,
			item,
		};
	},

	updateItems( items ) {
		return {
			type: TYPES.UPDATE_ITEMS,
			items,
		};
	},

	updateItem( item ) {
		return {
			type: TYPES.UPDATE_ITEM,
			item,
		};
	},

	deleteItems( itemIds ) {
		return {
			type: TYPES.DELETE_ITEMS,
			itemIds,
		};
	},

	deleteItem( itemId ) {
		return {
			type: TYPES.DELETE_ITEM,
			itemId,
		};
	},

	setIsCreatingItems( isCreating ) {
		return {
			type: TYPES.SET_IS_CREATING,
			isCreating,
		};
	},

	setCreatingError( error ) {
		return {
			type: TYPES.SET_CREATING_ERROR,
			error
		};
	},

	setIsRequestingItems( isRequesting ) {
		return {
			type: TYPES.SET_IS_REQUESTING,
			isRequesting,
		};
	},

	setRequestingError( error ) {
		return {
			type: TYPES.SET_REQUESTING_ERROR,
			error
		};
	},

	setIsUpdatingItems( isUpdating ) {
		return {
			type: TYPES.SET_IS_UPDATING,
			isUpdating,
		};
	},

	setUpdatingError( error ) {
		return {
			type: TYPES.SET_UPDATING_ERROR,
			error,
		};
	},

	setIsDeletingItems( isDeleting ) {
		return {
			type: TYPES.SET_IS_DELETING,
			isDeleting,
		};
	},

	setDeletingError( error ) {
		return {
			type: TYPES.SET_DELETING_ERROR,
			error,
		};
	},

} )
