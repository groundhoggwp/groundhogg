import {
    FETCH_REPORT_NAVBAR,
    SELECTED_REPORT_NAVBAR_CHANGE,
    FETCH_REPORTS_IN_PAGE,
    FETCH_REPORT,
    REPORT_DROPDOWN_CHANGE, FETCH_ALL_REPORTS
} from "./types";
import axios from "axios";
import {array, element, func} from "prop-types";

export const fetchNavBar = () => (dispatch, useState) => {
    axios.get(groundhogg.rest_base + '/pages').then(
        (response) => {

            dispatch({
                type: FETCH_REPORT_NAVBAR,
                payload: response.data.pages,
            });

            dispatch({
                type: SELECTED_REPORT_NAVBAR_CHANGE,
                payload: Object.keys(response.data.pages)[0],

            });

            getPages(dispatch, Object.keys(response.data.pages)[0], useState);
        }
    );
};

export const changeSelectedNav = (selected) => (dispatch, useState) => {

    if (String(useState().reportNavBar.pageSelected) !== String(selected)) {
        dispatch({
            type: SELECTED_REPORT_NAVBAR_CHANGE,
            payload: selected,
        });


        getPages(dispatch, selected, useState);
    }
}

/**
 * get the page and reload all the reports in the list
 * @param dispatch
 * @param selected
 * @param useState
 */
export const getPages = (dispatch, selected, useState) => {

    if (!useState().reportNavBar.pages[selected]) {

        // fetch the page report if it does not exists
        axios.post(groundhogg.rest_base + '/pages', {
            page: selected
        }).then(
            function (response) {
                if (response.data.reports) {
                    dispatch({
                        type: FETCH_REPORTS_IN_PAGE,
                        payload: {
                            page: selected, // response.data.page  // another variable with the same value
                            reports: response.data.reports,
                        }
                    });

                    getReports(dispatch, useState);
                }
            }
        );
    } else {
        getReports(dispatch, useState);
    }

};


const getReports = (dispatch, useState) => {

    let selected = useState().reportNavBar.pageSelected;
    let reports = [];
    //get list of reports
    console.log( useState().reportNavBar.pages[selected].rows.flat(10));


    useState().reportNavBar.pages[selected].rows.flat(10).map((element) => {
        if (element.id || element.type === 'multi-report') {

            if (element.type === 'multi-report') {
                element.reports.map((report) => {
                    reports.push(report.id);
                })
            }else {
                reports.push(element.id);
            }
        }
    });

    let startDate = useState().reportData.start;
    let endDate = useState().reportData.end;
    let data = useState().reportData.data;


    // dispatch({
    //     type: FETCH_REPORT,
    //     payload: {
    //         id: reportId,
    //         isLoading: true,
    //         data: {},
    //         isFailed: false
    //     }
    // });

    try {

        axios.post(groundhogg.rest_base + '/reports', {
            reports: reports,
            start_date: startDate.format('MMMM D, YYYY'),
            end_date: endDate.format('MMMM D, YYYY'),
            data: data
        }).then(
            (response) => {
                // if (response.data.hasOwnProperty('chart')) {
                //
                //     dispatch({
                //         type: FETCH_REPORT,
                //         payload: {
                //             id: reportId,
                //             data: response.data,
                //             isFailed: false,
                //             isLoading: false
                //
                //         }
                //     });
                //
                //     // if (type === 'ddl' && Object.keys(response.data.chart)[0]) {
                //     //     dispatch({
                //     //         type: REPORT_DROPDOWN_CHANGE,
                //     //         payload: {
                //     //             id: reportId,
                //     //             value: Object.keys(response.data.chart)[0],
                //     //         }
                //     //     });
                //     //
                //     //     dispatch({
                //     //         type: FETCH_REPORTS_IN_PAGE,
                //     //         payload: {},
                //     //     });
                //     //
                //     //     getPages(dispatch,selected ,useState);
                //     // }
                //
                // } else {
                //     dispatch({
                //         type: FETCH_REPORT,
                //         payload: {
                //             id: reportId,
                //             data: {},
                //             isFailed: true,
                //             isLoading: false
                //
                //         }
                //     });
                // }
                dispatch({
                    type: FETCH_ALL_REPORTS,
                    payload: response.data
                });

            }
        ).catch((e) => {
            dispatch({
                type: FETCH_REPORT,
                payload: {
                    id: reportId,
                    data: {},
                    isFailed: true,
                    isLoading: false
                }
            });
        });
    } catch (e) {
        console.log(e);
    }

};

