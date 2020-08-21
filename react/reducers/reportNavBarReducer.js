import {FETCH_REPORT_NAVBAR,FETCH_REPORTS_IN_PAGE, SELECTED_REPORT_NAVBAR_CHANGE} from '../actions/types'


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
            return {
                ...state,
                pageSelected: action.payload

            }
        case FETCH_REPORTS_IN_PAGE :
            return {
                ...state,
                pages: action.payload
            }
        default:
            return state;
    }
};
