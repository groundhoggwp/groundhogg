/**
 * Get tag from state tree.
 *
 * @param {Object} state - Reducer state
 * @param {Array} item - Option name
 */
export const getItem = ( state, item ) => {
	return state.item;
};

/**
 * Get tags from state tree.
 *
 * @param {Object} state - Reducer state
 */
export const getItems = ( state ) => {
	return state.items;
};

/**
 * Determine if an tags request resulted in an error.
 *
 * @param {Object} state - Reducer state
 * @param {string} name - Option name3
 */
export const getItemsRequestingError = ( state, name ) => {
	return state.requestingErrors[ name ] || false;
};

/**
 * Determine if tags are being updated.
 *
 * @param {Object} state - Reducer state
 */
export const isItemsUpdating = ( state ) => {
	return state.isUpdating || false;
};

/**
 * Determine if tags are being updated.
 *
 * @param {Object} state - Reducer state
 */
export const isItemsRequesting = ( state ) => {
	return state.isRequesting || false;
};

/**
 * Determine if an tags update resulted in an error.
 *
 * @param {Object} state - Reducer state
 */
export const getItemsUpdatingError = ( state ) => {
	return state.updatingError || false;
};

/**
 * Gets endpoint provided by initial state.
 *
 * @param {*} state
 */
export const getEndpoint = ( state ) => {
	return state.endpoint || '';
}