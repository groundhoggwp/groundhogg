/**
 * Get tag from state tree.
 *
 * @param {Object} state - Reducer state
 * @param {Array} name - Option name
 */
export const getTag = ( state, item ) => {
	return state.item;
};

/**
 * Get tags from state tree.
 *
 * @param {Object} state - Reducer state
 */
export const getTags = ( state ) => {
	return state.items;
};

/**
 * Determine if an tags request resulted in an error.
 *
 * @param {Object} state - Reducer state
 * @param {string} name - Option name
 */
export const getTagsRequestingError = ( state, name ) => {
	return state.requestingErrors[ name ] || false;
};

/**
 * Determine if tags are being updated.
 *
 * @param {Object} state - Reducer state
 */
export const isTagsUpdating = ( state ) => {
	return state.isUpdating || false;
};

/**
 * Determine if tags are being updated.
 *
 * @param {Object} state - Reducer state
 */
export const isTagsRequesting = ( state ) => {
	return state.isRequesting || false;
};

/**
 * Determine if an tags update resulted in an error.
 *
 * @param {Object} state - Reducer state
 */
export const getTagsUpdatingError = ( state ) => {
	return state.updatingError || false;
};

export const getEndpoint = ( state ) => {
	return state.endpoint || '';
}