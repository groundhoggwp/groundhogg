/**
 * Internal dependencies
 */
import TYPES from './action-types';

const funnelReducer = (
	state = {
		item: {},
		error: {},
		isRequesting: false,
		isUpdating: true,
		isCreating: false,
		isDeleting: false,
	},
	{
		type,
		error,
		item,
		isCreating,
		isUpdating,
		isDeleting,
	}
) => {
	switch ( type ) {
		// Create
		case TYPES.CREATE_STEP:
			return {
				...state,
				item,
			}
		case TYPES.SET_IS_CREATING:
			state = {
				...state,
				isCreating,
			}
			break
		case TYPES.SET_CREATING_ERROR:
			state = {
				...state,
				creatingErrors: {
					error,
				},
				isCreating: false,
			}
			break

		// Update
		case TYPES.UPDATE_STEP:
			return {
				...state,
				item,
			}
		case TYPES.SET_IS_UPDATING:
			state = {
				...state,
				isUpdating,
			}
			break
		case TYPES.SET_CREATING_ERROR:
			state = {
				...state,
				creatingErrors: {
					error,
				},
				isCreating: false,
			}
			break

		// Delete
		case TYPES.DELETE_STEP:
			return {
				...state,
				item,
			}
		case TYPES.SET_IS_DELETING:
			state = {
				...state,
				isDeleting,
			}
			break
		case TYPES.SET_CREATING_ERROR:
			state = {
				...state,
				creatingErrors: {
					error,
				},
				isCreating: false,
			}
			break
		default:
			return state;
	}
};

export default funnelReducer;
