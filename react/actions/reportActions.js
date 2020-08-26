import {FETCH_REPORT, REPORT_DROPDOWN_CHANGE} from './types'
import axios from "axios";

export const fetchReport = (reportId, type = '') => (dispatch, useState) => {

    if (useState().reportData.data[reportId]){
        type ='' ;
    }
    getReport(dispatch, reportId, useState().reportData.start, useState().reportData.end, type);
};

const getReport = (dispatch, reportId, startDate, endDate, type) => {

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

                    if (type === 'ddl') {
                        dispatch({
                            type: REPORT_DROPDOWN_CHANGE,
                            payload: {
                                id: reportId,
                                value: Object.keys(response.data.chart)[0],
                            }
                        });
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

