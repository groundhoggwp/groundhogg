import {
  FETCH_CONTACTS_FAILED,
  FETCH_CONTACTS_REQUEST,
  FETCH_CONTACTS_SUCCESS,
  CHANGE_QUERY,
  FETCH_MORE_CONTACTS_SUCCESS,
  CLEAR_ITEMS,
  CHANGE_CONTEXT,
  CLEAR_STATE,
  SHOW_CONTACT_FILTERS,
  SELECT_ALL_ITEMS,
  SELECT_ITEM,
  SELECT_SOME_ITEMS
} from '../actions/types'

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
    case CHANGE_CONTEXT:
      return {
        ...state,
        context: {
          ...state.context,
          ...action.payload
        }
      }
    case CHANGE_QUERY:
      return {
        ...state,
        query: {
          ...state.query,
          ...action.payload
        }
      }
    case CLEAR_STATE:
      return {
        ...state,
        ...initialState
      }
    case FETCH_CONTACTS_REQUEST:
      return {
        ...state,
        fetching: true,
      }
    case FETCH_CONTACTS_SUCCESS:
      return {
        ...state,
        fetching: false,
        data: action.payload.contacts,
        total: action.payload.total,
        error: {}
      }
    case FETCH_MORE_CONTACTS_SUCCESS:
      return {
        ...state,
        fetching: false,
        data: [
          ...state.data,
          ...action.payload
        ],
        error: {}
      }
    case FETCH_CONTACTS_FAILED:
      return {
        ...state,
        fetching: false,
        data: [],
        error: action.payload
      }
    case CLEAR_ITEMS:
      return {
        ...state,
        data: [],
      }
    case SHOW_CONTACT_FILTERS:
      return {
        ...state,
        showFilters: true
      }
    default:
      return state;
  }
}