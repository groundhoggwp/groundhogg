/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { initialState } from './initial-state';

const contactsReducer = (
	state = initialState,
	{
		type,
		error,
		itemData, // Single entity item object
		itemId, // Single entity ID
		items, // Collection of item objects
		itemIds, // Collection of item IDs
		tags,
		files,
		others,
		isAdding,
		isRequesting,
		isUpdating,
		isDeleting,
		isMerging,
		context,
		queryVars
	}
) => {
	switch ( type ) {
		case TYPES.CHANGE_CONTEXT:
			return {
				...state,
				context
		}
		case TYPES.CHANGE_QUERY:
			return {
				...state,
				queryVars
		}
		case TYPES.CLEAR_STATE:
			return {
				...state,
				...initialState
		}
		case TYPES.CLEAR_ITEMS:
			return {
				...state,
				items: [],
		}
		case TYPES.SHOW_CONTACT_FILTERS:
			return {
				...state,
				showFilters: true
		}
		default:
			return state;
	}
};

export default contactsReducer;