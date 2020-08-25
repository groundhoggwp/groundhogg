import {FETCH_REPORT_NAVBAR, SELECTED_REPORT_NAVBAR_CHANGE, FETCH_REPORTS_IN_PAGE} from "./types";
import axios from "axios";

export const fetchNavBar = () => dispatch => {
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

            getPages(dispatch, Object.keys(response.data.pages)[0]);
        }
    );
};

export const changeSelectedNav = (selected) => (dispatch, getState) => {

    if (String(getState().reportNavBar.pageSelected) !== String(selected)) {
        dispatch({
            type: SELECTED_REPORT_NAVBAR_CHANGE,
            payload: selected,

        });

        dispatch({
            type: FETCH_REPORTS_IN_PAGE,
            payload: {},
        });

        getPages(dispatch, selected);
    }
}

export const getPages = (dispatch, selected) => {

    axios.post(groundhogg.rest_base + '/pages', {
        page: selected
    }).then(
        function (response) {
            if (response.data.reports) {
                dispatch({
                    type: FETCH_REPORTS_IN_PAGE,
                    payload: response.data.reports,
                });
            }
        }
    );
};

