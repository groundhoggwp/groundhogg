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

export function switchEditorMode( mode ) {
	return {
		type: 'SWITCH_MODE',
		mode,
	};
}
/**
 * Returns an action object used to open/close the inserter.
 *
 * @param {boolean} value A boolean representing whether the inserter should be opened or closed.
 * @return {Object} Action object.
 */
export function setIsInserterOpened( value ) {
	return {
		type: 'SET_IS_INSERTER_OPENED',
		value,
	};
}
