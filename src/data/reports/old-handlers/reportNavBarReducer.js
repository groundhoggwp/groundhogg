import {FETCH_REPORT_NAVBAR,FETCH_REPORTS_IN_PAGE, SELECTED_REPORT_NAVBAR_CHANGE} from '../actions/types'
import {mergeDeep} from "../functions";


const initialState = {
    pageSelected: '',
    pageList: {},
    pages : {}
};

export default (state = initialState, action) => {
    switch (action.type) {
        case FETCH_REPORT_NAVBAR:
            return {
                ...state,
                pageList: action.payload,
            };
        case SELECTED_REPORT_NAVBAR_CHANGE :
            console.log("report nav bar change ");
            return {
                ...state,
                pageSelected: action.payload

            }
        case FETCH_REPORTS_IN_PAGE :
            console.log("fetch repots in the page");
            return mergeDeep(state , {
                pages: {
                    [action.payload.page]: action.payload.reports
                }
            });
        default:
            return state;
    }
};
