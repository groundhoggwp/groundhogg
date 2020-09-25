/**
 * Internal dependencies
 */
import TYPES from './action-types';

const coreReducer = (
	state = {
		errors: {},
		requesting: {},
	},
	{
		error,
		isRequesting,
		type,
	}
) => {
	switch ( type ) {
		case TYPES.CORE_ACTION:
			state = {
				...state,
				param: {},
			};
			break;
	}
	return state;
};

export default coreReducer;
