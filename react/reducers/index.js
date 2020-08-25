import {combineReducers} from 'redux'
import sideBarReducer from './sideBarReducer'
import videoModalReducer from './videoModalReducer'
import contactListReducer from './contactListReducer'
import selectionReducer from './selectionReducer'
import bulkJobReducer from './bulkJobReducer'
import reportNavBarReducer from "./reportNavBarReducer";
import reportReducer from "./reportReducer";
import reportDateReducer from "./reportDateReducer";

export default combineReducers({
  sideBar: sideBarReducer,
  videoModal: videoModalReducer,
  contactList: contactListReducer,
  itemSelection: selectionReducer,
  bulkJob: bulkJobReducer,
  reportNavBar : reportNavBarReducer,
  reports : reportReducer,
  reportDate : reportDateReducer
})