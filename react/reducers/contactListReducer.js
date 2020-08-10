import {
  FETCH_CONTACTS_FAILED,
  FETCH_CONTACTS_REQUEST,
  FETCH_CONTACTS_SUCCESS,
  CHANGE_QUERY,
  FETCH_MORE_CONTACTS_SUCCESS, CLEAR_ITEMS
} from '../actions/types'

const initialState = {
  fetching: false,
  count: 0,
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
    case CHANGE_QUERY:
      return {
        ...state,
        query: {
          ...state.query,
          ...action.payload
        }
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
        data: action.payload,
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
    default:
      return state;
  }
}