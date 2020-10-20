/**
 * Internal dependencies
 */
import * as actions from './actions';
import reducer from './reducer';

import {
	registerBaseObjectStore,
	getStoreName
} from '../base-object';

const STORE_NAME = 'steps';

registerBaseObjectStore( STORE_NAME, {
	reducer,
	actions
} );

export const STEPS_STORE_NAME = getStoreName( STORE_NAME );
