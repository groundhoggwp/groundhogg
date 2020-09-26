import {FETCH_REPORT, FETCH_REPORTS_IN_PAGE, REPORT_DROPDOWN_CHANGE} from './types'
import axios from "axios";
import {getPages} from "./reportNavBarActions";


/**
 *  Fetches the single report
 * @param reportId
 * @param type
 * @returns {function(*=, *=, *): void}
 */
export const fetchReport = (reportId, type = '') => (dispatch, useState ) => {

    if (useState().reportData.data[reportId]){
        type ='' ;
    }
    getReport(dispatch, reportId, type ,useState);
};

/**
 *
 * @param dispatch
 * @param reportId
 * @param type
 * @param useState
 */
const getReport = (dispatch, reportId, type ,useState  ) => {

    let startDate = useState().reportData.start;
    let endDate = useState().reportData.end;
    let data = useState().reportData.data;
    let selected = useState().reportNavBar.pageSelected;

    dispatch({
        type: FETCH_REPORT,
        payload: {
            id: reportId,
            isLoading: true,
            data: {},
            isFailed: false
        }
    });

    try {

        axios.post(groundhogg.rest_base + '/reports', {
            id: reportId,
            start_date: startDate.format('MMMM D, YYYY'),
            end_date: endDate.format('MMMM D, YYYY'),
            data : data
        }).then(
            (response) => {
                if (response.data.hasOwnProperty('chart')) {

                    dispatch({
                        type: FETCH_REPORT,
                        payload: {
                            id: reportId,
                            data: response.data,
                            isFailed: false,
                            isLoading: false

                        }
                    });

                    if (type === 'ddl' && Object.keys(response.data.chart)[0]) {
                        dispatch({
                            type: REPORT_DROPDOWN_CHANGE,
                            payload: {
                                id: reportId,
                                value: Object.keys(response.data.chart)[0],
                            }
                        });

                        dispatch({
                            type: FETCH_REPORTS_IN_PAGE,
                            payload: {},
                        });

                        getPages(dispatch,selected ,useState);
                    }

                } else {
                    dispatch({
                        type: FETCH_REPORT,
                        payload: {
                            id: reportId,
                            data: {},
                            isFailed: true,
                            isLoading: false

                        }
                    });
                }
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