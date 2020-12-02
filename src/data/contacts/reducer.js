/**
 * Internal dependencies
 */
import TYPES from './action-types'
import { initialState } from './initial-state'

const contactsReducer = (
  state = initialState,
  {
    type,
    error,
    tags,
    files,
    totalFiles,
    others,
    isMerging,
    context,
    queryVars,
    isRequesting,
    isDeleting,
    isAdding
  }
) => {
  switch (type) {
    case TYPES.CHANGE_CONTEXT:
      return {
        ...state,
        context
      }
    case TYPES.CHANGE_QUERY:
      return {
        ...state,
        queryVars
      }
    case TYPES.CLEAR_STATE:
      return {
        ...state,
        ...initialState
      }
    case TYPES.CLEAR_ITEMS:
      return {
        ...state,
        items: [],
      }
    case TYPES.SHOW_CONTACT_FILTERS:
      return {
        ...state,
        showFilters: true
      }
    case TYPES.RECEIVE_CONTACT_FILES :
      return {
        ...state,
        files,
        totalFiles,
      }
    case TYPES.SET_IS_REQUESTING_FILES :
      return {
        ...state,
        isRequesting,
      }
    case TYPES.SET_IS_ADDING_FILES :
      return {
        ...state,
        isAdding,
      }
    case TYPES.SET_IS_DELETING_FILES :
      return {
        ...state,
        isDeleting,
      }
    default:
      return state
  }
}

export default contactsReducer