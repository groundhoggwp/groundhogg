/**
 * Internal dependencies
 */
import TYPES from './action-types';

const emailsReducer = (
	state = {
		// isUpdating: false,
		emails: [],
		// requestingErrors: {}
	},
	{ type, emails, error, isUpdating, name }
) => {
	switch ( type ) {
		case TYPES.GET_EMAILS:
			state = {
				...state,
				...emails,
			};
			break;
	}
	return state;
};

export default emailsReducer;
