/**
 * Get item from state tree.
 *
 * @param {Object} state - Reducer state
 * @param id
 */
export const getItem = ( state, id=false ) => {
	// console.log( state );

	if ( id && state.items ){
		return state.items.find( _item => _item.ID === id ) || state.item;
	}

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
 * Get the most recently created items
 *
 * @param state
 * @returns {*}
 */
export const getCreatedItems = ( state ) => {
	// if ( state.extendedReducer ) {
	// 	return state.reducer.createdItems;
	// }
	return state.createdItems;
}

/**
 * Get items from state tree.
 *
 * @param {Object} state - Reducer state
 */
export const getTotalItems = ( state ) => {
	// if ( state.extendedReducer ) {
	// 	return state.reducer.totalItems;
	// }
	return state.totalItems;
};

/**
 * Determine if items are being updated.
 *
 * @param {Object} state - Reducer state
 */
export const isItemsUpdating = ( state ) => {
	// if ( state.extendedReducer ) {
	// 	return state.reducer.isUpdating || false;
	// }
	return state.isUpdating || false;
};

/**
 * Determine if items are being updated.
 *
 * @param {Object} state - Reducer state
 */
export const isItemsCreating = ( state ) => {
	// if ( state.extendedReducer ) {
	// 	return state.reducer.isCreating || false;
	// }
	return state.isCreating || false;
};

/**
 * Determine if items are being updated.
 *
 * @param {Object} state - Reducer state
 */
export const isItemsRequesting = ( state ) => {
	// if ( state.extendedReducer ) {
	// 	return state.reducer.isRequesting || false;
	// }
	return state.isRequesting || false;
};

/**
 * Determine if an items create request resulted in an error.
 *
 * @param {Object} state - Reducer state
 * @param {string} name - Option name
 */
export const getItemsCreatingError = ( state, name ) => {
	// if ( state.extendedReducer ) {
	// 	return state.reducer.creatingErrors[ name ] || false;
	// }

	return state.creatingErrors[ name ] || false;
};

/**
 * Determine if an items read request resulted in an error.
 *
 * @param {Object} state - Reducer state
 * @param {string} name - Option name
 */
export const getItemsRequestingError = ( state, name ) => {
	// if ( state.extendedReducer ) {
	// 	return state.reducer.requestingErrors[ name ] || false;
	// }

	return state.requestingErrors[ name ] || false;
};

/**
 * Determine if an items update resulted in an error.
 *
 * @param {Object} state - Reducer state
 * @param name
 */
export const getItemsUpdatingError = ( state, name ) => {
	// if ( state.extendedReducer ) {
	// 	return state.reducer.updatingErrors[ name ] || false;
	// }

	return state.updatingErrors[ name ] || false;
};
