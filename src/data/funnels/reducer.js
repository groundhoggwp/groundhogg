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
		item,
		others,
		isMerging,
		context,
		queryVars
	}
) => {
	switch ( type ) {

		case TYPES.CREATE_STEP:
			return {
				...state,
				item,
			}
		case TYPES.DELETE_STEP:
			return {
				...state,
				item,
			}
		case TYPES.UPDATE_STEP:
			return {
				...state,
				item,
			}
		default:
			return state;
	}
};

export default funnelReducer;
