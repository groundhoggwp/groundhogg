export const getBulkJobsError = ( state, selector ) => {
	return state.errors[ selector ] || false;
};
