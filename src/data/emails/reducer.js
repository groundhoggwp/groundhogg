/**
 * Internal dependencies
 */
import TYPES from './action-types';

const emailsReducer = (
	state = {
		isRequesting: false,
		items: [],
		requestingErrors: {}
	},
	{ type, items, error, isRequesting, name }
) => {
	switch ( type ) {
		case TYPES.RECEIVE_EMAILS:
			state = {
				...state,
				items : items.map( ( item ) => { return item.data } )
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
	}
	return state;
};

export default emailsReducer;
