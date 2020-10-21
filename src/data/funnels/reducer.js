/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { initialState } from './initial-state';

const funnelReducer = (
	state = initialState,
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
			
		// Delete
		case TYPES.DELETE_STEP:
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
		default:
			return state;
	}
};

export default funnelReducer;
