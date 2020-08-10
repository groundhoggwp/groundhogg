import {
  CHANGE_QUERY, CLEAR_ITEMS,
  FETCH_CONTACTS_FAILED,
  FETCH_CONTACTS_REQUEST,
  FETCH_CONTACTS_SUCCESS,
  FETCH_MORE_CONTACTS_SUCCESS
} from './types'
import axios from 'axios'

const fetchContactsRequest = () => {
  return {
    type: FETCH_CONTACTS_REQUEST
  }
}

const fetchContactsSuccess = (contacts) => {
  return {
    type: FETCH_CONTACTS_SUCCESS,
    payload: contacts
  }
}

const fetchMoreContactsSuccess = (contacts) => {
  return {
    type: FETCH_MORE_CONTACTS_SUCCESS,
    payload: contacts
  }
}

const fetchContactsFailed = (error) => {
  return {
    type: FETCH_CONTACTS_FAILED,
    payload: error
  }
}

export const changeQuery = (queryVars) => {
  return {
    type: CHANGE_QUERY,
    payload: queryVars
  }
}

export const clearItems = () => (dispatch) => {
  dispatch({
    type: CLEAR_ITEMS,
  })
}

export const updateQuery = (queryVars) => (dispatch) => {
  dispatch(changeQuery(queryVars))
}

export const resetQuery = () => (dispatch) => {
  dispatch(changeQuery({
    number: 20,
    offset: 0,
    orderby: 'ID',
    order: 'DESC'
  }))
}

export const fetchContacts = () => (dispatch, getState) => {
  const {
    query
  } = getState().contactList

  dispatch(fetchContactsRequest())

  axios.get(groundhogg.rest_base + '/contacts', {
    params: {
      query: query
    }
  }).then(response => {
    dispatch(fetchContactsSuccess(response.data.contacts))
  }).catch(error => fetchContactsFailed(error))
}

export const fetchMoreContacts = () => (dispatch, getState) => {
  const {
    query
  } = getState().contactList

  dispatch(fetchContactsRequest())

  axios.get(groundhogg.rest_base + '/contacts', {
    params: {
      query: query
    }
  }).then(response => {
    dispatch(fetchMoreContactsSuccess(response.data.contacts))
  }).catch(error => fetchContactsFailed(error))
}