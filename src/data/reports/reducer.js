/**
 * Internal dependencies
 */
import TYPES from './action-types';

const reports = (
	state = {
		itemErrors: {},
		reports: {},
		statErrors: {},
		// stats: {},
	},
	{ type, items, stats, error, resourceName }
) => {
	switch ( type ) {
		case TYPES.FETCH_ALL_REPORTS:
			return {
				...state,
				items: { ...state.reports, [ resourceName ]: reports },
			};
		default:
			return state;
	}
};

export default reports;
