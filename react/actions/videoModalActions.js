import { OPEN_VIDEO_MODAL, CLOSE_VIDEO_MODAL } from './types'

export const openVideoModal = ( src, title ) => dispatch => {

  dispatch({
    type: OPEN_VIDEO_MODAL,
    src: src,
    title: title
  })
}

export const closeVideoModal = () => dispatch => {

  dispatch({
    type: CLOSE_VIDEO_MODAL,
  })
}