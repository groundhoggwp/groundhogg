import { OPEN_VIDEO_MODAL, CLOSE_VIDEO_MODAL } from '../actions/types'

const initialState = {
  show: false,
  src: '',
  title: '',
}

export default function (state = initialState, action ) {
  switch (action.type) {
    case OPEN_VIDEO_MODAL:
      return {
        ...state,
        show: true,
        src: action.src,
        title: action.title
      }
    case CLOSE_VIDEO_MODAL:
      return {
        ...state,
        show: false,
        src: '',
        title: ''
      }
    default:
      return state;
  }
}