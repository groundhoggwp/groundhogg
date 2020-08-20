import { EXPAND_SIDEBAR, COLLAPSE_SIDEBAR } from '../actions/types'

const initialState = {
  status: 'collapsed'
}

export default function (state = initialState, action ) {
  switch (action.type) {
    case EXPAND_SIDEBAR:
      return {
        ...state,
        status: 'expanded'
      }
    case COLLAPSE_SIDEBAR:
      return {
        ...state,
        status: 'collapsed'
      }
    default:
      return state;
  }
}