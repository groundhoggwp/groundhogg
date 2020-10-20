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
		tags,
		files,
		others,
		isMerging,
		context,
		queryVars
	}
) => {
	switch ( type ) {
		case TYPES.UPDATE_STEP:
			return {
				...state,
				context
		}
		case TYPES.ADD_STEP:
			return {
				...state,
				queryVars
		}
		case TYPES.DELETE_STEP:
			return {
				...state,
				...initialState
		}
		default:
			return state;
	}
};

export default funnelReducer;
