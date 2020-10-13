/**
 * Internal dependencies
 */
import TYPES from './action-types';

const coreReducer = (
	state = {
		snackbarMessage : '',
		snackbarOpen : false,
		snackbarSeverity : 'success'
	},
	{
		snackbarMessage,
		snackbarSeverity,
		type
	}
) => {
	switch ( type ) {
		case TYPES.OPEN_SNACKBAR:
			state = {
				...state,
				snackbarSeverity,
				snackbarMessage,
				snackbarOpen : true,
			};
			break;
		case TYPES.CLEAR_SNACKBAR:
			state = {
				...state,
				snackbarOpen : false,
			};
	}
	return state;
};

export default coreReducer;
