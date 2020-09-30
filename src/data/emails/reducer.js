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
		case TYPES.FETCH_CONTACTS_SUCCESS:
		return {
		  ...state,
		  fetching: false,
		  data: action.payload.contacts,
		  total: action.payload.total,
		  error: {}
		}
		case TYPES.FETCH_CONTACTS_FAILED:
		return {
			...state,
			fetching: false,
			data: [],
			error: action.payload
		}

	//   case TYPES.CHANGE_CONTEXT:
	// 	return {
	// 	  ...state,
	// 	  context: {
	// 		...state.context,
	// 		...action.payload
	// 	  }
	// 	}
	//   case TYPES.CHANGE_QUERY:
	// 	return {
	// 	  ...state,
	// 	  query: {
	// 		...state.query,
	// 		...action.payload
	// 	  }
	// 	}
	//   case TYPES.CLEAR_STATE:
	// 	return {
	// 	  ...state,
	// 	  ...initialState
	// 	}
	//   case TYPES.FETCH_CONTACTS_REQUEST:
	// 	return {
	// 	  ...state,
	// 	  fetching: true,
	// 	}
	//
	//   case TYPES.FETCH_MORE_CONTACTS_SUCCESS:
	// 	return {
	// 	  ...state,
	// 	  fetching: false,
	// 	  data: [
	// 		...state.data,
	// 		...action.payload
	// 	  ],
	// 	  error: {}
	// 	}
	//
	//   case TYPES.CLEAR_ITEMS:
	// 	return {
	// 	  ...state,
	// 	  data: [],
	// 	}
	//   case TYPES.SHOW_CONTACT_FILTERS:
	// 	return {
	// 	  ...state,
	// 	  showFilters: true
	// 	}
	//   default:
	// 	return state;
	// }
  }
