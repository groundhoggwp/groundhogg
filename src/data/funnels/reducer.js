/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { INITIAL_STATE as BASE_OBJECT_INITIAL_STATE } from 'data/base-object/constants'

const INITIAL_STATE = {
	...BASE_OBJECT_INITIAL_STATE,
	edges: []
}

const funnelReducer = (
	state = INITIAL_STATE,
	{
		type,
		error,
		item,
		funnelId,
		edges,
		isCreating,
		isUpdating,
		isDeleting,
	}
) => {
	switch ( type ) {
		case TYPES.UPDATE_EDGES:

			if ( state.item.ID === funnelId ){
				state.item.edges = edges
			}

			state.items.find( item => item.ID === funnelId )?.edges = edges

			break;
		// Create
		case TYPES.CREATE_STEP:
			state = {
				...state,
				item: item,
				items: state.items.map(_item => _item.ID === item.ID ? item : _item),
			}
			break
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
			state = {
				...state,
				item: item,
				items: state.items.map(_item => _item.ID === item.ID ? item : _item),
			}
			break
		case TYPES.SET_IS_UPDATING:
			state = {
				...state,
				isUpdating,
			}
			break
		case TYPES.SET_UPDATING_ERROR:
			state = {
				...state,
				updatingErrors: {
					error,
				},
				isUpdating: false,
			}
			break

		// Delete
		case TYPES.DELETE_STEP:
			state = {
				...state,
				item: item,
				items: state.items.map(_item => _item.ID === item.ID ? item : _item),
			}
			break
		case TYPES.SET_IS_DELETING:
			state = {
				...state,
				isDeleting,
			}
			break
		case TYPES.SET_DELETING_ERROR:
			state = {
				...state,
				deletingErrors: {
					error,
				},
				isDeleting: false,
			}
			break
	}

	return state;
};

export default funnelReducer;
