
/**
 * External dependencies
 */
import { includes } from 'lodash';

/**
 * WordPress dependencies
 */
import { createRegistrySelector } from '@wordpress/data';

export const getSnackbarMessage = ( state ) => {
	return state.snackbarMessage;
};

export const getSnackbarSeverity = ( state ) => {
	return state.snackbarSeverity;
};

export const getSnackbarMenuOpen = ( state ) => {
	return state.snackbarOpen;
};

/**
 * Returns the current editing mode.
 *
 * @param {Object} state Global application state.
 *
 * @return {string} Editing mode.
 */
export function getEditorMode( state ) {
	return getPreference( state, 'editorMode', 'visual' );
}

/**
 * Returns the preferences (these preferences are persisted locally).
 *
 * @param {Object} state Global application state.
 *
 * @return {Object} Preferences Object.
 */
export function getPreferences( state ) {
	return state.preferences;
}

/**
 *
 * @param {Object} state         Global application state.
 * @param {string} preferenceKey Preference Key.
 * @param {*}      defaultValue  Default Value.
 *
 * @return {*} Preference Value.
 */
export function getPreference( state, preferenceKey, defaultValue ) {
	const preferences = getPreferences( state );
	const value = preferences[ preferenceKey ];
	return value === undefined ? defaultValue : value;
}

/**
 * Returns true if the editor sidebar is opened.
 *
 * @param {Object} state Global application state
 *
 * @return {boolean} Whether the editor sidebar is opened.
 */
export const isEditorSidebarOpened = createRegistrySelector(
	( select ) => () => {
		const activeGeneralSidebar = select(
			'core/interface'
		).getActiveComplementaryArea( 'gh/v4/core' );
		return includes(
			[ 'edit-email/email', 'edit-email/block' ],
			activeGeneralSidebar
		);
	}
);

/**
 * Returns the current active general sidebar name, or null if there is no
 * general sidebar active. The active general sidebar is a unique name to
 * identify either an editor or plugin sidebar.
 *
 * Examples:
 *
 *  - `edit-email/block`
 *  - `edit-email/email`
 *
 * @param {Object} state Global application state.
 *
 * @return {?string} Active general sidebar name.
 */
export const getActiveGeneralSidebarName = createRegistrySelector(
	( select ) => () => {
		return select( 'core/interface' ).getActiveComplementaryArea(
			'gh/v4/core'
		);
	}
);

/**
 * Returns true if the inserter is opened.
 *
 * @param  {Object}  state Global application state.
 *
 * @return {boolean} Whether the inserter is opened.
 */
export function isInserterOpened( state ) {
	return state.isInserterOpened;
}