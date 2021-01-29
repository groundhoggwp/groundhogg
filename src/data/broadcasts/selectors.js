
export const getIsScheduling = (state) => {
    return state.isScheduling || false;
};

export const getSchedulingError = (state) => {
    return state.schedulingErrors || false;
}