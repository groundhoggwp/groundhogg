/**
 * Internal dependencies
 */
import * as selectors from './selectors';
import * as actions from './actions';
import reducer from './reducer';
import * as resolvers from './resolvers';
import applyMiddlewares from './middlewares';

import {
	registerBaseObjectStore,
	getStoreName
} from '../base-object';

const STORE_NAME = 'core';

const store = registerBaseObjectStore( STORE_NAME, {
	selectors,
	actions,
	reducer,
	resolvers,
	persist: [ 'preferences' ],
} );

applyMiddlewares( store );

export const CORE_STORE_NAME = getStoreName( STORE_NAME );