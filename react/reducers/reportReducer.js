import {FETCH_REPORT,FETCH_ALL_REPORTS} from '../actions/types'


const initialState = {};

export default (state = initialState, action) => {
    switch (action.type) {
        case FETCH_REPORT:
            console.log("fetch single report");
            return {
                ...state,
                [action.payload.id]: {
                    isLoading: action.payload.isLoading,
                    isFailed: action.payload.isFailed,
                    data: action.payload.data
                }
            };
        case FETCH_ALL_REPORTS:
            console.log("fetch all repots in the page");
            return action.payload;
        default:
            return state;
    }
};
