import {FETCH_REPORT} from '../actions/types'


const initialState = {};


export default (state = initialState, action) => {
    switch (action.type) {
        case FETCH_REPORT:
            return {
                ...state,
                [action.payload.id]: {
                    isLoading: action.payload.isLoading,
                    isFailed: action.payload.isFailed,
                    data: action.payload.data
                }
            };
        default:
            return state;
    }
};
