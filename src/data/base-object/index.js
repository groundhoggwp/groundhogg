/**
 * External dependencies
 */
import {
	registerStore,
	combineReducers
} from '@wordpress/data';

import { assign } from 'lodash';

/**
 * Internal dependencies
 */
import * as baseSelectors from './selectors';
import BaseActions from './actions';
import BaseResolver from './resolvers';
import controls from '../controls';
import reducer from './reducer';
import { NAMESPACE } from '../constants'

export function registerBaseObjectStore (endpoint, options) {
	const storeName = NAMESPACE + '/' + endpoint

	options = options || {}

	let baseActions = new BaseActions( storeName );
	let baseResolver = new BaseResolver( storeName, baseActions );

	let extendedReducer = options.reducer;
	const storeArgs = {
		reducer      : options.reducer   ? combineReducers( { reducer, extendedReducer } ): reducer,
		actions      : options.actions   ? assign( baseActions  , options.actions )   : baseActions ,
		selectors    : options.selectors ? assign( baseSelectors, options.selectors ) : baseSelectors,
		resolvers    : options.resolvers ? assign( baseResolver, options.resolvers )  : baseResolver,
		controls     : options.controls  ? assign( controls , options.controls )      : controls,
		initialState : options.initialState || {},
	}

	return registerStore( storeName, storeArgs );
}

export function getStoreName (ghEndpoint) {
  return NAMESPACE + '/' + ghEndpoint
}