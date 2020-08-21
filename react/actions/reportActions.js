import {FETCH_REPORT} from './types'
import axios from "axios";

export const fetchReport = (reportId, startDate, endDate) => dispatch => {
    getReport(dispatch, reportId, startDate, endDate);
};

const getReport = (dispatch, reportId, startDate, endDate) => {

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

        axios.post(groundhogg.rest_base  + '/reports', {
            id: reportId,
            start_date: ' July 21, 2020',
            end_date: 'August 20, 2020'
        }).then(
            (response) => {
                if (response.data.hasOwnProperty('chart')) {

                    // console.log("table"  + response.data);

                    dispatch({
                        type: FETCH_REPORT,
                        payload: {
                            id: reportId,
                            data: response.data,
                            isFailed: false,
                            isLoading: false

                        }
                    });
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
            console.log(e);
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

