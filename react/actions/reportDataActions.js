import {getPages} from "./reportNavBarActions";
import { FETCH_REPORTS_IN_PAGE, REPORT_DATE_CHANGE, REPORT_DROPDOWN_CHANGE} from './types';

export const dateChanged = (startDate, endDate) => (dispatch,useState) => {

    //set dates in the state
    dispatch({
        type: REPORT_DATE_CHANGE,
        payload: {
            startDate: startDate,
            endDate: endDate
        }
    });


    // // set reports to null
    // dispatch({
    //     type: FETCH_REPORTS_IN_PAGE,
    //     payload: {},
    // });

    // reset reports
    getPages(dispatch,useState().reportNavBar.pageSelected ,useState );

};


export const dropDownChanged = (reportId, value) => (dispatch, useState) => {
    //set dates in the state
    if (value) {
        dispatch({
            type: REPORT_DROPDOWN_CHANGE,
            payload: {
                id: reportId,
                value: value,
            }
        });
        getPages(dispatch,useState().reportNavBar.pageSelected ,useState);
    }
};
