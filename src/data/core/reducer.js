/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { INITIAL_STATE as BASE_OBJECT_INITIAL_STATE } from 'data/base-object/constants'

const INITIAL_STATE = {
	...BASE_OBJECT_INITIAL_STATE
}

const coreReducer = (
	state = INITIAL_STATE,
	{
		type,
		value,
		key,
		mode,
		isAllowed,
		snackbarSeverity,
		snackbarMessage,
	}
) => {
	switch ( type ) {
		case TYPES.RECEIVE_USER_PERMISSION:
			state = {
				...state,
				[ key ]: isAllowed,
			}
			break
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
			break;
		case TYPES.SET_IS_INSERTER_OPENED:
			state = {
				...state,
				isInserterOpened : value,
			};
			break;
		case TYPES.SWITCH_MODE:
			state = {
				...state,
				mode,
			};
			break;
	}

	return state;
};

export default coreReducer;