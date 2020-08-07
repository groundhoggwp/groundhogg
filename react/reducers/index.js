import {combineReducers} from 'redux'
import sideBarReducer from './sideBarReducer'

export default combineReducers({
  sideBar: sideBarReducer
})