/**
 * Internal dependencies
 */
import * as actions from './actions';
import reducer from './reducer';

import {
	registerBaseObjectStore,
	getStoreName
} from '../base-object';

const STORE_NAME = 'reports';

registerBaseObjectStore( STORE_NAME, {
	reducer,
	actions
} );

export const REPORTS_STORE_NAME = getStoreName( STORE_NAME );
