/**
 * External dependencies
 */
import {
	registerStore,
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
import { mergeReducers } from 'data/utils'
import { INITIAL_STATE } from 'data/base-object/constants'

export function registerBaseObjectStore (endpoint, options) {
	const storeName = NAMESPACE + '/' + endpoint

	options = options || {}

	let baseActions = new BaseActions( storeName );
	let baseResolver = new BaseResolver( storeName, baseActions );

	let extendedReducer = options.reducer;

	const storeArgs = {
		...options,
		reducer      : options.reducer   ? mergeReducers( { reducer, extendedReducer } ): reducer,
		actions      : options.actions   ? assign( baseActions  , options.actions )   : baseActions ,
		selectors    : options.selectors ? assign( baseSelectors, options.selectors ) : baseSelectors,
		resolvers    : options.resolvers ? assign( baseResolver, options.resolvers )  : baseResolver,
		controls     : options.controls  ? assign( controls , options.controls )      : controls,
		initialState : options.initialState || INITIAL_STATE,
	}

	return registerStore( storeName, storeArgs );
}

export function getStoreName (ghEndpoint) {
  return NAMESPACE + '/' + ghEndpoint
}