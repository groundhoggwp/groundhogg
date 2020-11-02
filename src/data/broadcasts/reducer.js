/**
 * Internal dependencies
 */

import TYPES from './action-types';
import { initialState } from './initial-state';

const broadcastsReducer = (
	state = initialState,
	{ type, isScheduling ,error}
) => {
	switch ( type ) {

		case TYPES.SET_IS_SCHEDULING :
			state = {
				...state,
				isScheduling,
			}
			break;
		case TYPES.SET_SCHEDULING_ERROR :
			state = {
				...state,
				schedulingErrors: {
					error
				},
				isScheduling: false,
			}
			break;
	}
	return state;
};

export default broadcastsReducer;