/**
 * Internal dependencies
 */
import TYPES from './action-types';

const tagsReducer = (
	state = { isUpdating: false, requestingErrors: {} },
	{ type, tags, error, isUpdating, name }
) => {
	switch ( type ) {
		case TYPES.RECEIVE_TAGS:
			state = {
				...state,
				...tags,
			};
			break;
		case TYPES.SET_IS_UPDATING:
			state = {
				...state,
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

export default tagsReducer;