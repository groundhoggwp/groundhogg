/**
 * External dependencies
 */

import { registerStore } from '@wordpress/data';

/**
 * Internal dependencies
 */
import * as selectors from './selectors';
import * as actions from './actions';
import controls from '../controls';
import reducer from './reducer';
import * as resolvers from './resolvers';
import { NAMESPACE } from '../constants'

export function registerBaseObjectStore ( endpoint, options ) {
	const storeName = NAMESPACE + '/' + endpoint;
	const initialState = { endpoint : storeName };
	registerStore( storeName, {
		reducer,
		actions,
		controls,
		selectors,
		resolvers,
		initialState
	} );
}

export function getStoreName( ghEndpoint ) {
	return NAMESPACE + '/' + ghEndpoint;
}