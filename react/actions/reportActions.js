import {FETCH_REPORT} from './types'
import axios from "axios";

export const fetchReport = (reportId, startDate, endDate) => (dispatch,useState ) => {

    getReport(dispatch, reportId, useState().reportDate.start, useState().reportDate.end);
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
            start_date: startDate.format('MMMM D, YYYY'),
            end_date: endDate.format('MMMM D, YYYY')
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

