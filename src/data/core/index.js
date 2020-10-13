/**
 * Internal dependencies
 */
import * as selectors from './selectors';
import * as actions from './actions';
import reducer from './reducer';

import {
	registerBaseObjectStore,
	getStoreName
} from '../base-object';

const STORE_NAME = 'core';

registerBaseObjectStore( STORE_NAME, {
	selectors,
	actions,
	reducer
} );

export const CORE_STORE_NAME = getStoreName( STORE_NAME );

