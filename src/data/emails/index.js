/**
 * External dependencies
 */

import { registerStore, select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';
import * as selectors from './selectors';
import * as actions from './actions';
import * as resolvers from './resolvers';
import controls from '../controls';
import reducer from './reducer';

const storeSelectors = select( STORE_NAME );

if ( ! storeSelectors ) {
	registerStore( STORE_NAME, {
		reducer,
		actions,
		controls,
		selectors,
		resolvers,
	} );
}

export const EMAILS_STORE_NAME = STORE_NAME;
