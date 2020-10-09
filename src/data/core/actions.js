/**
 * Internal dependencies
 */
import TYPES from './action-types';

/**
 * Core actions.
 */

export function showSnackbar( message, severity ) {

	return {
		type: TYPES.OPEN_SNACKBAR,
		snackbarMessage : message,
		snackbarSeverity : severity,
	}
};

export function clearSnackbar() {

	return {
		type: TYPES.CLEAR_SNACKBAR
	}
};