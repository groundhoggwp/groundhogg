/**
 * Internal dependencies
 */
import TYPES from './action-types';

const tagsReducer = (
	state = {
		isUpdating: false,
		isRequesting: false,
		items: [],
		requestingErrors: {}
	},
	{ type, items, error, isUpdating, isRequesting, name }
) => {
	switch ( type ) {
		case TYPES.RECEIVE_TAGS:
			state = {
				...state,
				items : items.map( ( item ) => { return item.data } )
			};
			break;
		case TYPES.SET_IS_UPDATING:
			state = {
				...state,
				...items,
				isUpdating,
			};
		break;
		case TYPES.SET_IS_REQUESTING:
			state = {
				...state,
				...items,
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

export default tagsReducer;