/**
 * Internal dependencies
 */
import * as selectors from './selectors';
import * as actions from './actions';
import * as resolvers from './resolvers';
import reducer from './reducer';

import {
	registerBaseObjectStore,
	getStoreName
} from '../base-object';

const STORE_NAME = 'contacts';

registerBaseObjectStore( STORE_NAME, {
	selectors,
	actions,
	resolvers,
	reducer
} );

export const CONTACTS_STORE_NAME = getStoreName( STORE_NAME );