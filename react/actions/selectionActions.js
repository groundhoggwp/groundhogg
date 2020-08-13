import {
  SELECT_ALL_ITEMS,
  DESELECT_ALL_ITEMS,
  SELECT_ITEM,
  DESELECT_ITEM,
  SELECT_SOME_ITEMS,
  DESELECT_SOME_ITEMS, SHIFT_KEY_DOWN, SHIFT_KEY_UP,
} from './types'

export const selectAllItems = (items) => (dispatch) => {
  dispatch({
    type: SELECT_ALL_ITEMS,
  })
}

export const deselectAllItems = () => (dispatch) => {
  dispatch({
    type: DESELECT_ALL_ITEMS,
  })
}

export const selectSomeItems = (items, selected) => (dispatch) => {
  dispatch({
    type: SELECT_SOME_ITEMS,
    payload: items,
    selected: selected,
  })
}

export const deselectSomeItems = (items, selected) => (dispatch) => {
  dispatch({
    type: DESELECT_SOME_ITEMS,
    payload: items,
    selected: selected,
    callback: (item, payload) => {
      return !payload.includes(item)
    },
  })
}

export const selectItem = (item) => (dispatch) => {
  dispatch({
    type: SELECT_ITEM,
    payload: item,
  })
}

export const deselectItem = (item) => (dispatch) => {
  dispatch({
    type: DESELECT_ITEM,
    payload: item,
    callback: (item, payload) => {
      return item !== payload
    },
  })
}

export const shiftKeyDown = () => (dispatch) => {
  dispatch({type: SHIFT_KEY_DOWN})
}

export const shiftKeyUp = () => (dispatch) => {
  dispatch({type: SHIFT_KEY_UP})
}
