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
import BaseResolver from './resolvers';
import controls from '../controls';
import reducer from './reducer';
import { NAMESPACE } from '../constants'

export function registerBaseObjectStore (endpoint, options) {
  const storeName = NAMESPACE + '/' + endpoint

  options = options || {}

  let baseResolver = new BaseResolver( storeName );

	const storeArgs = {
		reducer      : options.reducer   ? assign( reducer, options.reducer )         : reducer,
		controls     : options.controls  ? assign( controls , options.controls )      : controls,
		actions      : options.actions   ? assign( baseActions  , options.actions )   : baseActions ,
		selectors    : options.selectors ? assign( baseSelectors, options.selectors ) : baseSelectors,
		resolvers    : options.resolvers ? assign( baseResolver, options.resolvers ) : baseResolver,
		initialState : options.initialState || {},
	}

	console.debug( baseResolver )

	// storeArgs.resolvers.setEndpoint( storeName );
	// storeArgs.actions.getEndpoint   = (endpoint = storeName) => endpoint

	return registerStore( storeName, storeArgs );
}

export function getStoreName (ghEndpoint) {
  return NAMESPACE + '/' + ghEndpoint
}