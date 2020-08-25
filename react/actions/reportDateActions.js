import {getPages} from "./reportNavBarActions";
import {FETCH_REPORTS_IN_PAGE, REPORT_DATE_CHANGE} from './types';

export const dateChanged = (startDate, endDate) => (dispatch, getState) => {


    //set dates in the state
    dispatch({
        type: REPORT_DATE_CHANGE,
        payload: {
            startDate: startDate,
            endDate: endDate
        }
    });


    // set reports to null
    dispatch({
        type: FETCH_REPORTS_IN_PAGE,
        payload: {},
    });

    // reset reports
    getPages(dispatch,getState().reportNavBar.pageSelected);


};

