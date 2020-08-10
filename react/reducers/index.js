import {combineReducers} from 'redux'
import sideBarReducer from './sideBarReducer'
import videoModalReducer from './videoModalReducer'
import contactListReducer from './contactListReducer'

export default combineReducers({
  sideBar: sideBarReducer,
  videoModal: videoModalReducer,
  contactList: contactListReducer
})