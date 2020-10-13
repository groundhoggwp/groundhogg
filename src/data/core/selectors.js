export const getSnackbarMessage = ( state ) => {
	const { extendedReducer } = state;
	return extendedReducer.snackbarMessage;
};

export const getSnackbarSeverity = ( state ) => {
	const { extendedReducer } = state;
	return extendedReducer.snackbarSeverity;
};

export const getSnackbarMenuOpen = ( state ) => {
	const { extendedReducer } = state;
	return extendedReducer.snackbarOpen;
};
