/**
 * Internal dependencies
 */
import TYPES from './action-types';

const initialState = {
	isRequesting: false,
	isUpdating: false,
	isCreating: false,
	isDeleting: false,
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
};

const contactsReducer = (
	state = initialState,
	{
		type,
		payload,
		data,
		error,
		isUpdating,
		isDeleting,
		isCreating,
		isUpdating,
		isRequesting,
		name
	}
) => {
	switch ( type ) {
	  case TYPES.CHANGE_CONTEXT:
		return {
		  ...state,
		  context: {
			...state.context,
			...payload
		  }
		}
	  case TYPES.CHANGE_QUERY:
		return {
		  ...state,
		  query: {
			...state.query,
			...payload
		  }
		}
	  case TYPES.CLEAR_STATE:
		return {
		  ...state,
		  ...initialState
		}
	  case TYPES.FETCH_CONTACTS_REQUEST:
		return {
		  ...state,
		  isRequesting: true,
		}
	  case TYPES.FETCH_CONTACTS_SUCCESS:
		return {
		  ...state,
		  isRequesting: false,
		  data: payload.contacts,
		  total: payload.total,
		  error: {}
		}
	  case TYPES.FETCH_MORE_CONTACTS_SUCCESS:
		return {
		  ...state,
		  isRequesting: false,
		  data: [
			...state.data,
			...payload
		  ],
		  error: {}
		}
	  case TYPES.FETCH_CONTACTS_FAILED:
		return {
		  ...state,
		  isRequesting: false,
		  data: [],
		  error: payload
		}
	  case TYPES.CLEAR_ITEMS:
		return {
		  ...state,
		  data: [],
		}
	  case TYPES.SHOW_CONTACT_FILTERS:
		return {
		  ...state,
		  showFilters: true
		}
	  default:
		return state;
	}
};

export default contactsReducer;