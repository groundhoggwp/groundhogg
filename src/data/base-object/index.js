/**
 * External dependencies
 */

import { registerStore } from '@wordpress/data';
import { assign } from 'lodash';

/**
 * Internal dependencies
 */
import * as baseSelectors from './selectors';
import * as baseActions from './actions';
import * as baseControls from '../controls';
import * as baseReducer from './reducer';
import * as baseResolvers from './resolvers';
import { NAMESPACE } from '../constants'

export function registerBaseObjectStore (endpoint) {

	baseResolvers.getEndpoint = ( endpoint = storeName ) => endpoint;
  baseActions.getEndpoint   = (endpoint = storeName) => endpoint

	const storeArgs = {
		reducer      : options.reducer   ? assign( baseReducer  , options.reducer )   : baseReducer,
		actions      : options.actions   ? assign( baseActions  , options.actions )   : baseActions,
		selectors    : options.selectors ? assign( baseSelectors, options.selectors ) : baseSelectors,
		controls     : options.controls  ? assign( baseControls , options.controls )  : baseControls,
		resolvers    : options.resolvers ? assign( baseResolvers, options.resolvers ) : baseResolvers,
		initialState : options.initialState || {},
	}

	return registerStore( NAMESPACE + '/' + endpoint, storeArgs );
}

export function getStoreName (ghEndpoint) {
  return NAMESPACE + '/' + ghEndpoint
}