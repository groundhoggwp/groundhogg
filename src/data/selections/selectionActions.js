import TYPES from './action-types';

export const selectAllItems = (items) => (dispatch) => {
  dispatch({
    type: TYPES.SELECT_ALL_ITEMS,
  })
}

export const deselectAllItems = () => (dispatch) => {
  dispatch({
    type: TYPES.DESELECT_ALL_ITEMS,
  })
}

export const selectSomeItems = (items, selected) => (dispatch) => {
  dispatch({
    type: TYPES.SELECT_SOME_ITEMS,
    payload: items,
    selected: selected,
  })
}

export const deselectSomeItems = (items, selected) => (dispatch) => {
  dispatch({
    type: TYPES.DESELECT_SOME_ITEMS,
    payload: items,
    selected: selected,
    callback: (item, payload) => {
      return !payload.includes(item)
    },
  })
}

export const selectItem = (item) => (dispatch) => {
  dispatch({
    type: TYPES.SELECT_ITEM,
    payload: item,
  })
}

export const deselectItem = (item) => (dispatch) => {
  dispatch({
    type: TYPES.DESELECT_ITEM,
    payload: item,
    callback: (item, payload) => {
      return item !== payload
    },
  })
}

export const shiftKeyDown = () => (dispatch) => {
  dispatch({type: TYPES.SHIFT_KEY_DOWN})
}

export const shiftKeyUp = () => (dispatch) => {
  dispatch({type: TYPES.SHIFT_KEY_UP})
}
