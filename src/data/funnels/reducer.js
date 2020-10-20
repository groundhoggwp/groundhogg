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
		// queryVars
	}
) => {
	switch ( type ) {
		case TYPES.CREATE_STEP:
			return {
				...state,
				initialState
		}
		default:
			return state;
	}
};

export default funnelReducer;
