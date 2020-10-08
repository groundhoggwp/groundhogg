/**
 * Get item from state tree.
 *
 * @param {Object} state - Reducer state
 * @param {Array} itemId - Option name
 */
export const getItem = ( state, itemId ) => {
	return state.item;
};

/**
 * Get items from state tree.
 *
 * @param {Object} state - Reducer state
 */
export const getItems = ( state ) => {
	return state.items;
};

/**
 * Determine if an items read request resulted in an error.
 *
 * @param {Object} state - Reducer state
 * @param {string} name - Option name
 */
export const getItemsRequestingError = ( state, name ) => {
	return state.requestingErrors[ name ] || false;
};

/**
 * Determine if items are being updated.
 *
 * @param {Object} state - Reducer state
 */
export const isItemsUpdating = ( state ) => {
	return state.isUpdating || false;
};

/**
 * Determine if items are being updated.
 *
 * @param {Object} state - Reducer state
 */
export const isItemsRequesting = ( state ) => {
	return state.isRequesting || false;
};

/**
 * Determine if an items update resulted in an error.
 *
 * @param {Object} state - Reducer state
 */
export const getItemsUpdatingError = ( state ) => {
	return state.updatingError || false;
};