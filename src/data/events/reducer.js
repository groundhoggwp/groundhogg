/**
 * Internal dependencies
 */
import TYPES from './action-types';

const eventsReducer = (
	state = {
		isUpdating: false,
		isRequesting: false,
		events: [],
		requestingErrors: {}
	},
	{ type, events, error, isUpdating, name, isRequesting }
) => {
	switch ( type ) {
		case TYPES.RECEIVE_EVENTS:
			state = {
				...state,
				...events,
			};
			break;
		case TYPES.SET_IS_UPDATING:
			state = {
				...state,
				...events,
				isUpdating,
			};
			break;
		case TYPES.SET_IS_REQUESTING:
			state = {
				...state,
				...events,
				isRequesting,
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

export default eventsReducer;