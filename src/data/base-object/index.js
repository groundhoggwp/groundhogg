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
import controls from '../controls';
import reducer from './reducer';
import * as baseResolvers from './resolvers';
import { NAMESPACE } from '../constants'

export function registerBaseObjectStore (endpoint, options) {
  const storeName = NAMESPACE + '/' + endpoint

  options = options || {}

	baseResolvers.getEndpoint = ( endpoint = storeName ) => endpoint
  baseActions.getEndpoint   = (endpoint = storeName) => endpoint

	const storeArgs = {
		reducer      : options.reducer   ? assign( reducer  , options.reducer )   : reducer,
		controls     : options.controls  ? assign( controls , options.controls )  : controls,
		actions      : options.actions   ? assign( baseActions  , options.actions )   : baseActions,
		selectors    : options.selectors ? assign( baseSelectors, options.selectors ) : baseSelectors,
		resolvers    : options.resolvers ? assign( baseResolvers, options.resolvers ) : baseResolvers,
		initialState : options.initialState || {},
	}

	return registerStore( NAMESPACE + '/' + endpoint, storeArgs );
}

export function getStoreName (ghEndpoint) {
  return NAMESPACE + '/' + ghEndpoint
}