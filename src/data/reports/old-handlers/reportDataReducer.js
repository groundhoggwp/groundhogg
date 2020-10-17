import {REPORT_DATE_CHANGE, REPORT_DROPDOWN_CHANGE} from '../actions/types'
import moment from "moment";
import {mergeDeep} from "../functions";


const initialState = {
    start: moment().subtract(29, 'days'),
    end: moment(),
    data: {}
};

export default (state = initialState, action) => {
    switch (action.type) {
        case REPORT_DATE_CHANGE:
            return {
                ...state,
                start: action.payload.startDate,
                end: action.payload.endDate,
            };
        case REPORT_DROPDOWN_CHANGE:
            return mergeDeep( state, {data: {[action.payload.id]: action.payload.value}});
        default:
            return state;
    }
};
