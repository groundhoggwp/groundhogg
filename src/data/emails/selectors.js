/**
 * Get Emails from state tree.
 *
 * @param {Object} state - Reducer state
 */
export const getEmails = ( state ) => {
	return state.items;
};

/**
 * Determine if emails are being updated.
 *
 * @param {Object} state - Reducer state
 */
export const isEmailsRequesting = ( state ) => {
	return state.isRequesting || false;
};

/**
 * Determine if an emails request resulted in an error.
 *
 * @param {Object} state - Reducer state
 * @param {string} name - Option name
 */
export const getEmailsRequestingError = ( state, name ) => {
	return state.requestingErrors[ name ] || false;
};
