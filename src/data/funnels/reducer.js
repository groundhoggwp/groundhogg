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
		step,
		// files,
		// others,
		// isMerging,
		context,
		// queryVars
	}
) => {
	switch ( type ) {
		case TYPES.CREATE_STEP:
			return {
				...state,
				step: step
		}
		default:
			return state;
	}
};

export default funnelReducer;
