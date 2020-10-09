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
 * Get items from state tree.
 *
 * @param {Object} state - Reducer state
 */
export const getTotalItems = ( state ) => {
	return state.totalItems;
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
export const isItemsCreating = ( state ) => {
	return state.isCreating || false;
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
 * Determine if an items create request resulted in an error.
 *
 * @param {Object} state - Reducer state
 * @param {string} name - Option name
 */
export const getItemsCreatingError = ( state, name ) => {
	return state.creatingErrors[ name ] || false;
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
 * Determine if an items update resulted in an error.
 *
 * @param {Object} state - Reducer state
 * @param name
 */
export const getItemsUpdatingError = ( state, name ) => {
	return state.updatingErrors[name] || false;
};
