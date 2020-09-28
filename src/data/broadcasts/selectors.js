/**
 * Get tag from state tree.
 *
 * @param {Object} state - Reducer state
 * @param {Array} name - Option name
 */
export const getBroadcast = ( state, name ) => {
	return state[ name ];
};

/**
 * Get tag from state tree.
 *
 * @param {Object} state - Reducer state
 * @param {Array} name - Option name
 */
export const getBroadcasts  = ( state ) => {
	return state;
};

/**
 * Determine if an tags request resulted in an error.
 *
 * @param {Object} state - Reducer state
 * @param {string} name - Option name
 */
export const getBroadcastRequestingError = ( state, name ) => {
	return state.requestingErrors[ name ] || false;
};

/**
 * Determine if tags are being updated.
 *
 * @param {Object} state - Reducer state
 */
export const isBroadcastsUpdating = ( state ) => {
	return state.isUpdating || false;
};

/**
 * Determine if an tags update resulted in an error.
 *
 * @param {Object} state - Reducer state
 */
export const getBroadcastsUpdatingError = ( state ) => {
	return state.updatingError || false;
};