/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { PREFERENCES_DEFAULTS } from './defaults';

/**
 * External dependencies
 */
import { flow, get, omit, union, without } from 'lodash';

/**
 * WordPress dependencies
 */
import { combineReducers } from '@wordpress/data';

/**
 * Higher-order reducer creator which provides the given initial state for the
 * original reducer.
 *
 * @param {*} initialState Initial state to provide to reducer.
 *
 * @return {Function} Higher-order reducer.
 */
const createWithInitialState = ( initialState ) => ( reducer ) => {
	return ( state = initialState, action ) => reducer( state, action );
};

/**
 * Reducer returning the user preferences.
 *
 * @param {Object}  state                           Current state.
 * @param {string}  state.mode                      Current editor mode, either
 *                                                  "visual" or "text".
 * @param {Object}  action                          Dispatched action.
 *
 * @return {Object} Updated state.
 */
export const preferences = flow( [
	combineReducers,
	createWithInitialState( PREFERENCES_DEFAULTS ),
] )( {
	editorMode( state, action ) {
		if ( action.type === 'SWITCH_MODE' ) {
			return action.mode;
		}

		return state;
	},
} );

/**
 * Reducer tracking whether the inserter is open.
 *
 * @param {boolean} state
 * @param {Object}  action
 */
function isInserterOpened( state = false, action ) {
	switch ( action.type ) {
		case 'SET_IS_INSERTER_OPENED':
			return action.value;
	}
	return state;
}

const coreReducer = (
	state = {
		snackbarMessage : '',
		snackbarOpen : false,
		snackbarSeverity : 'success'
	},
	{
		snackbarMessage,
		snackbarSeverity,
		type
	}
) => {
	switch ( type ) {
		case TYPES.OPEN_SNACKBAR:
			state = {
				...state,
				snackbarSeverity,
				snackbarMessage,
				snackbarOpen : true,
			};
			break;
		case TYPES.CLEAR_SNACKBAR:
			state = {
				...state,
				snackbarOpen : false,
			};
	}
	return state;
};


export default combineReducers( {
	coreReducer,
	preferences,
	isInserterOpened,
} );
