import {
  SELECT_ALL_ITEMS,
  DESELECT_ALL_ITEMS,
  SELECT_ITEM,
  DESELECT_ITEM,
  SELECT_SOME_ITEMS,
  DESELECT_SOME_ITEMS, SHIFT_KEY_DOWN, SHIFT_KEY_UP,
} from '../actions/types'

const initialState = {
  selected: [],
  allSelected: false,
  isShiftKeyDown: false,
  lastSelection: null,
}

export default function (state = initialState, action) {

  switch (action.type) {
    case SELECT_ALL_ITEMS:
      return {
        ...state,
        allSelected: true,
        lastSelection: null,
      }
    case SELECT_SOME_ITEMS:
      return {
        ...state,
        selected: [
          ...state.selected,
          ...action.payload.filter( item => ! state.selected.includes( item ) ),
        ],
        lastSelection: action.selected,
      }
    case SELECT_ITEM:

      return {
        ...state,
        selected: [
          ...state.selected,
          action.payload,
        ],
        lastSelection: action.payload,
      }
    case DESELECT_ALL_ITEMS:
      return {
        ...state,
        selected: [],
        allSelected: false,
        lastSelection: null,
      }
    case DESELECT_SOME_ITEMS:
      return {
        ...state,
        selected: state.selected.filter(
          item => action.callback(item, action.payload)),
        allSelected: false,
        lastSelection: action.selected,
      }
    case DESELECT_ITEM:
      return {
        ...state,
        selected: state.selected.filter(
          item => action.callback(item, action.payload)),
        allSelected: false,
        lastSelection: action.payload,
      }
    case SHIFT_KEY_DOWN:
      return {
        ...state,
        isShiftKeyDown: true
      }
    case SHIFT_KEY_UP:
      return {
        ...state,
        isShiftKeyDown: false
      }
    default:
      return state
  }
}