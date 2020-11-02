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

export function switchEditorMode( editorMode ) {
	return {
		type: TYPES.SWITCH_MODE,
		editorMode,
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
		type: TYPES.SET_IS_INSERTER_OPENED,
		value,
	};
}

/**
 * Returns an action object used in signalling that the current user has
 * permission to perform an action on a REST resource.
 *
 * @param {string}  key       A key that represents the action and REST resource.
 * @param {boolean} isAllowed Whether or not the user can perform the action.
 *
 * @return {Object} Action object.
 */
export function receiveUserPermission( key, isAllowed ) {
	return {
		type: TYPES.RECEIVE_USER_PERMISSION,
		key,
		isAllowed,
	};
}
