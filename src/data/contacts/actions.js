/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { NAMESPACE } from '../constants';

export function setItems( itemType, query, items, totalCount ) {
	return {
		type: TYPES.SET_ITEMS,
		items,
		itemType,
		query,
		totalCount,
	};
}

export function setError( itemType, query, error ) {
	return {
		type: TYPES.SET_ERROR,
		itemType,
		query,
		error,
	};
}

  const fetchContactsRequest = () => {
	return {
	  type: TYPES.FETCH_CONTACTS_REQUEST,
	}
  }

  const fetchContactsSuccess = (data) => {
	return {
	  type: TYPES.FETCH_CONTACTS_SUCCESS,
	  payload: {
		contacts: data.contacts,
		total: data.count,
	  },
	}
  }

  const fetchMoreContactsSuccess = (contacts) => {
	return {
	  type: TYPES.FETCH_MORE_CONTACTS_SUCCESS,
	  payload: contacts,
	}
  }

  const fetchContactsFailed = (error) => {
	return {
	  type: TYPES.FETCH_CONTACTS_FAILED,
	  payload: error,
	}
  }

  export const changeQuery = (queryVars) => {
	return {
	  type: TYPES.CHANGE_QUERY,
	  payload: queryVars,
	}
  }

  export const clearItems = () => (dispatch) => {
	dispatch({
	  type: TYPES.CLEAR_ITEMS,
	})
  }

  export const updateQuery = (queryVars) => (dispatch) => {
	dispatch({
	  type: TYPES.CHANGE_QUERY,
	  payload: queryVars,
	})
  }

  export const resetQuery = () => (dispatch) => {
	dispatch(changeQuery({
	  number: 20,
	  offset: 0,
	  orderby: 'ID',
	  order: 'DESC',
	}))
  }

  export const changeContext = (context) => (dispatch) => {
	dispatch({
	  type: TYPES.CLEAR_ITEMS,
	  payload: context,
	})
  }

  export const clearState = () => (dispatch) => {
	dispatch({
	  type: TYPES.CLEAR_STATE,
	})
  }

  export const showContactFilters = () => (dispatch) => {
	dispatch({
	  type: TYPES.SHOW_CONTACT_FILTERS,
	})
  }

  export const fetchContacts = () => (dispatch, getState) => {
	const {
	  query,
	} = getState().contactList

	dispatch(fetchContactsRequest())

	axios.get(groundhogg.rest_base + '/contacts', {
	  params: {
		query: query,
	  },
	}).then(response => {
	  dispatch(fetchContactsSuccess(response.data))
	}).catch(error => fetchContactsFailed(error))
  }

  export const fetchMoreContacts = () => (dispatch, getState) => {
	const {
	  query,
	} = getState().contactList

	dispatch(fetchContactsRequest())

	axios.get(groundhogg.rest_base + '/contacts', {
	  params: {
		query: query,
	  },
	}).then(response => {
	  dispatch(fetchMoreContactsSuccess(response.data.contacts))
	}).catch(error => fetchContactsFailed(error))
  }