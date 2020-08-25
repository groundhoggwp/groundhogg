import { REPORT_DATE_CHANGE} from '../actions/types'
import moment from "moment";


const initialState = {
    start: moment().subtract(29, 'days'),
    end: moment(),
};

export default (state = initialState, action) => {
    switch (action.type) {
        case REPORT_DATE_CHANGE:
            return {
                ...state,
                start: action.payload.startDate,
                end: action.payload.endDate,
            };
        default:
            return state;
    }
};
