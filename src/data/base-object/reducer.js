/**
 * Internal dependencies
 */
import TYPES from './action-types'
import { INITIAL_STATE } from 'data/base-object/constants'

const reducer = (
  state = INITIAL_STATE,
  {
    type,
    items,
    item,
    itemIds,
    itemId,
    totalItems,
    error,
    isCreating,
    isRequesting,
    isUpdating,
    isDeleting,
  },
) => {
  switch (type) {
    case TYPES.CREATE_ITEM:
      state = {
        ...state,
        item: item,
        items: [item, ...state.items],
      }
      break
    case TYPES.CREATE_ITEMS:
      state = {
        ...state,
        items: [...items, ...state.items],
        createdItems: items,
      }
      break
    case TYPES.SET_IS_CREATING:
      state = {
        ...state,
        isCreating,
      }
      break
    case TYPES.SET_CREATING_ERROR:
      state = {
        ...state,
        creatingErrors: {
          error,
        },
        isCreating: false,
      }
      break
    case TYPES.RECEIVE_ITEM:
      state = {
        ...state,
        item: item,
      }
      break
    case TYPES.RECEIVE_ITEMS:
      state = {
        ...state,
        items,
        totalItems,
      }
      break
    case TYPES.SET_IS_REQUESTING:
      state = {
        ...state,
        isRequesting,
      }
      break
    case TYPES.SET_REQUESTING_ERROR:
      state = {
        ...state,
        requestingErrors: {
          error,
        },
        isRequesting: false,
      }
      break
    case TYPES.UPDATE_ITEM:
      state = {
        ...state,
        item: item,
        items: state.items.map(_item => _item.ID === item.ID ? item : _item),
      }
      break
    case TYPES.UPDATE_ITEMS:
      state = {
        ...state,
        items: state.items.map(
          _item => items.find(__item => _item.ID === __item.ID) || _item),
        updatedItems: items,
      }
      break
    case TYPES.SET_IS_UPDATING:
      state = {
        ...state,
        isUpdating,
      }
      break
    case TYPES.SET_UPDATING_ERROR:
      state = {
        ...state,
        updatingErrors: {
          error,
        },
        isUpdating: false,
      }
      break
    case TYPES.DELETE_ITEM:
      state = {
        ...state,
        items: state.items.filter(existing => existing.ID !== itemId),
        totalItems: state.totalItems - 1,
      }
      break
    case TYPES.DELETE_ITEMS:
      state = {
        ...state,
        items: state.items.filter(existing => itemIds.indexOf(existing.ID) < 0),
        totalItems: state.totalItems - itemIds.length,
      }
      break
    case TYPES.SET_IS_DELETING:
      state = {
        ...state,
        isDeleting,
      }
      break
    case TYPES.SET_DELETING_ERROR:
      state = {
        ...state,
        deletingErrors: {
          error,
        },
        isDeleting: false,
      }
      break
  }

  // console.log( state );

  return state
}

export default reducer
