import {
  CHANGE_QUERY, CLEAR_ITEMS, CLEAR_STATE,
  FETCH_CONTACTS_FAILED,
  FETCH_CONTACTS_REQUEST,
  FETCH_CONTACTS_SUCCESS,
  FETCH_MORE_CONTACTS_SUCCESS, SHOW_CONTACT_FILTERS,
} from './types'
import axios from 'axios'

const fetchContactsRequest = () => {
  return {
    type: FETCH_CONTACTS_REQUEST,
  }
}

const fetchContactsSuccess = (data) => {
  return {
    type: FETCH_CONTACTS_SUCCESS,
    payload: {
      contacts: data.contacts,
      total: data.count,
    },
  }
}

const fetchMoreContactsSuccess = (contacts) => {
  return {
    type: FETCH_MORE_CONTACTS_SUCCESS,
    payload: contacts,
  }
}

const fetchContactsFailed = (error) => {
  return {
    type: FETCH_CONTACTS_FAILED,
    payload: error,
  }
}

export const changeQuery = (queryVars) => {
  return {
    type: CHANGE_QUERY,
    payload: queryVars,
  }
}

export const clearItems = () => (dispatch) => {
  dispatch({
    type: CLEAR_ITEMS,
  })
}

export const updateQuery = (queryVars) => (dispatch) => {
  dispatch({
    type: CHANGE_QUERY,
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
    type: CLEAR_ITEMS,
    payload: context,
  })
}

export const clearState = () => (dispatch) => {
  dispatch({
    type: CLEAR_STATE,
  })
}

export const showContactFilters = () => (dispatch) => {
  dispatch({
    type: SHOW_CONTACT_FILTERS,
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