import {combineReducers} from 'redux'
import sideBarReducer from './sideBarReducer'
import videoModalReducer from './videoModalReducer'
import contactListReducer from './contactListReducer'
import selectionReducer from './selectionReducer'

export default combineReducers({
  sideBar: sideBarReducer,
  videoModal: videoModalReducer,
  contactList: contactListReducer,
  itemSelection: selectionReducer
})