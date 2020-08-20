import {
  BULK_JOB_ERROR,
  BULK_JOB_FINISHED,
  BULK_JOB_INIT,
  BULK_JOB_PROCESSED_ITEMS,
} from '../actions/types'

const initialState = {
  show: false,
  start: false,
  action: '',
  actionName: '',
  numComplete: 0,
  numRemaining: 0,
  numItemsPerRequest: 10,
  totalItems: 0,
  context: {},
  lastResponse: null,
  error: null,
  onError: (error) => {
    console.debug( error )
  },
  onFinish: (response) => {
    console.debug( response )
  },
}

export default function (state = initialState, action) {
  switch (action.type) {
    // reset the state when finished
    case BULK_JOB_ERROR:
      return {
        ...state,
        ...initialState,
        error: action.payload
      }
    case BULK_JOB_FINISHED:
      return {
        ...state,
        ...initialState,
      }
    case BULK_JOB_PROCESSED_ITEMS:
      return {
        ...state,
        start: false,
        numComplete: state.numComplete + state.numItemsPerRequest,
        numRemaining: state.totalItems - state.numItemsPerRequest,
      }
    case BULK_JOB_INIT:
      return {
        ...state,
        ...action.payload,
        show: true,
        start: true,
        error: null,
        complete: false,
      }
    default:
      return state
  }
}