/**
 * Get tag from state tree.
 *
 * @param {Object} state - Reducer state
 * @param {Array} name - Option name
 */
export const getEvent = ( state, name ) => {
	return state[ name ];
};

/**
 * Get tag from state tree.
 *
 * @param {Object} state - Reducer state
 * @param {Array} name - Option name
 */
export const getEvents = ( state ) => {
	return state;
};

/**
 * Determine if an tags request resulted in an error.
 *
 * @param {Object} state - Reducer state
 * @param {string} name - Option name
 */
export const getEventsRequestingError = ( state, name ) => {
	return state.requestingErrors[ name ] || false;
};

/**
 * Determine if tags are being updated.
 *
 * @param {Object} state - Reducer state
 */
export const isEventsUpdating = ( state ) => {
	return state.isUpdating || false;
};


/**
 * Determine if tags are being updated.
 *
 * @param {Object} state - Reducer state
 */
export const isEventsRequesting = ( state ) => {
	return state.isRequesting || false;
};

/**
 * Determine if an tags update resulted in an error.
 *
 * @param {Object} state - Reducer state
 */
export const getEventsUpdatingError = ( state ) => {
	return state.updatingError || false;
};