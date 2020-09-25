/**
 * External dependencies
 */

import { select, registerStore } from '@wordpress/data';
import { controls } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';
import * as selectors from './selectors';
import * as actions from './actions';
import * as resolvers from './resolvers';
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

export const ONBOARDING_STORE_NAME = STORE_NAME;
