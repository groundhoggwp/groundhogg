/**
 * Internal dependencies
 */
import { getResourceName } from '../utils';




/**
 * Determine if items are being updated.
 *
 * @param {Object} state - Reducer state
 */
export const isCreatingStep = ( state ) => {
	// if ( state.extendedReducer ) {
	// 	return state.reducer.isUpdating || false;
	// }
	return state.isCreating || false;
};

/**
 * Determine if items are being updated.
 *
 * @param {Object} state - Reducer state
 */
export const isUpdatingStep = ( state ) => {
	// if ( state.extendedReducer ) {
	// 	return state.reducer.isCreating || false;
	// }
	return state.isUpdating || false;
};
/**
 * Determine if items are being updated.
 *
 * @param {Object} state - Reducer state
 */
export const isDeletingStep = ( state ) => {
	// if ( state.extendedReducer ) {
	// 	return state.reducer.isCreating || false;
	// }
	return state.isDeleting || false;
};
