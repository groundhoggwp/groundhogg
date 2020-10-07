/**
 * Internal dependencies
 */
import TYPES from './action-types';

const reducer = (
	state = {
		isCreating: false,
		isRequesting: false,
		isUpdating: false,
		isDeleting: false,
		items: [],
		item: {},
		creatingErrors: {},
		requestingErrors: {},
		updatingErrors: {},
		deletingErrors: {},
	},
	{
		type,
		items,
		item,
		itemIds,
		itemId,
		error,
		isCreating,
		isRequesting,
		isUpdating,
		isDeleting,
	}
) => {
	switch ( type ) {
		case TYPES.CREATE_ITEM:
			state = {
				...state,
				items: [ ...state.items, item ]
			};
			break;
		case TYPES.CREATE_ITEMS:
			state = {
				...state,
				items: [ ...state.items, ...items ]
			};
			break;
		case TYPES.SET_IS_CREATING:
			state = {
				...state,
				...items,
				isCreating,
			};
			break;
		case TYPES.SET_CREATING_ERROR:
			state = {
				...state,
				creatingErrors: {
					error,
				},
				isCreating: false
			};
			break;
		case TYPES.RECEIVE_ITEM:
			state = {
				...state,
				item: item,
				items: [ ...state.items, item ]
			};
			break;
		case TYPES.RECEIVE_ITEMS:
			state = {
				...state,
				items
			};
			break;
		case TYPES.SET_IS_REQUESTING:
			state = {
				...state,
				...items,
				isRequesting,
			};
			break;
		case TYPES.SET_REQUESTING_ERROR:
			state = {
				...state,
				requestingErrors: {
					error,
				},
				isRequesting: false
			};
			break;
		case TYPES.UPDATE_ITEM:
			state = {
				...state,
				items : state.items
					.filter( existing => existing.ID !== item.ID )
					.concat( [ item ] )
			};
			break;
		case TYPES.UPDATE_ITEMS:
			state = {
				...state,
				items : state.items
					.filter( existing => items.map( item => item.ID ).indexOf( existing.ID ) < 0 )
					.concat( items )
			};
			break;
		case TYPES.SET_IS_UPDATING:
			state = {
				...state,
				...items,
				isUpdating,
			};
		break;
		case TYPES.SET_UPDATING_ERROR:
			state = {
				...state,
				updatingErrors: {
					error,
				},
				isUpdating: false,
			};
			break;
		case TYPES.DELETE_ITEM:
			state = {
				...state,
				items : state.items
					.filter( existing => existing.ID !== itemId )
			};
			break;
		case TYPES.DELETE_ITEMS:
			state = {
				...state,
				items : state.items
					.filter( existing => itemIds.indexOf( existing.ID ) < 0 )
			};
			break;
		case TYPES.SET_IS_DELETING:
			state = {
				...state,
				...items,
				isDeleting,
			};
		break;
		case TYPES.SET_DELETING_ERROR:
				state = {
				...state,
				deletingErrors: {
					error,
				},
				isDeleting: false,
			};
			break;
	}
	return state;
};

export default reducer;