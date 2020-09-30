/**
 * Internal dependencies
 */
import TYPES from './action-types';

const initialState = {
	fetching: false,
	showFilters: false,
	total: 0,
	context: {},
	selected: [],
	query: {
	  number: 20,
	  offset: 0,
	  orderby: 'ID',
	  order: 'DESC',
	},
	data: [],
	error: {},
}

  export default function (state = initialState, action ) {
	switch (action.type) {
		case TYPES.FETCH_EMAILS_SUCCESS:
		return {
		  ...state,
		  fetching: false,
		  data: action.payload.emails,
		  total: action.payload.total,
		  error: {}
		}
		case TYPES.FETCH_EMAILS_FAILED:
		return {
			...state,
			fetching: false,
			data: [],
			error: action.payload
		}
	}
}
