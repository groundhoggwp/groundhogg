/**
 * Get tag from state tree.
 *
 * @param {Object} state - Reducer state
 * @param {Array} name - Option name
 */
export const getFunnel = ( state, item ) => {
	return state.item;
};

/**
 * Get tags from state tree.
 *
 * @param {Object} state - Reducer state
 */
export const getFunnels = ( state ) => {
	return state.items;
};

/**
 * Determine if an tags request resulted in an error.
 *
 * @param {Object} state - Reducer state
 * @param {string} name - Option name
 */
export const getFunnelsRequestingError = ( state, name ) => {
	return state.requestingErrors[ name ] || false;
};

/**
 * Determine if tags are being updated.
 *
 * @param {Object} state - Reducer state
 */
export const isFunnelsUpdating = ( state ) => {
	return state.isUpdating || false;
};

/**
 * Determine if tags are being updated.
 *
 * @param {Object} state - Reducer state
 */
export const isFunnelsRequesting = ( state ) => {
	return state.isRequesting || false;
};

/**
 * Determine if an tags update resulted in an error.
 *
 * @param {Object} state - Reducer state
 */
export const getFunnelsUpdatingError = ( state ) => {
	return state.updatingError || false;
};