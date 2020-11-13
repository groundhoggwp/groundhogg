/**
 * Determine if items are being updated.
 *
 * @param {Object} state - Reducer state
 */
export const isCreatingStep = ( state ) => {
	return state.isCreating || false;
};

/**
 * Determine if items are being updated.
 *
 * @param {Object} state - Reducer state
 */
export const isUpdatingStep = ( state ) => {
	return state.isUpdating || false;
};
/**
 * Determine if items are being updated.
 *
 * @param {Object} state - Reducer state
 */
export const isDeletingStep = ( state ) => {

	return state.isDeleting || false;
};


/**
 * Determine if items are being updated.
 *
 * @param {Object} state - Reducer state
 */
export const getFunnel = ( state ) => {
	return state.item || {};
};

/**
 *
 * The ID of the current item
 *
 * @param state
 * @returns {boolean}
 */
export const getCurrentId = ( state ) => {
	return state.item ? state.item.ID : false;
}
