/**
 * Internal dependencies
 */
import TYPES from './action-types';

const broadcastsReducer = (
	state = {
		isUpdating: false,
		broadcasts: [],
		requestingErrors: {}
	},
	{ type, broadcasts, error, isUpdating, name }
) => {
	switch ( type ) {
		case TYPES.RECEIVE_BROADCASTS:
			state = {
				...state,
				...broadcasts,
			};
			break;
		case TYPES.SET_IS_UPDATING:
			state = {
				...state,
				...broadcasts,
				isUpdating,
			};
			break;
		case TYPES.SET_REQUESTING_ERROR:
			state = {
				...state,
				requestingErrors: {
					[ name ]: error,
				},
			};
			break;
		case TYPES.SET_UPDATING_ERROR:
			state = {
				...state,
				error,
				updatingError: error,
				isUpdating: false,
			};
			break;
	}
	return state;
};

export default broadcastsReducer;