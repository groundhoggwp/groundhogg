import { EXPAND_SIDEBAR, COLLAPSE_SIDEBAR } from './types'

export const expandSidebar = () => dispatch => {
  dispatch({
    type: EXPAND_SIDEBAR,
  })
}

export const collapseSidebar = () => dispatch => {
  dispatch({
    type: COLLAPSE_SIDEBAR,
  })
}