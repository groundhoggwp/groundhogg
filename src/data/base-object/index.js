/**
 * External dependencies
 */

import { select, registerStore } from '@wordpress/data';

/**
 * Internal dependencies
 */
import * as selectors from './selectors';
import * as actions from './actions';
import controls from '../controls';
import reducer from './reducer';
import * as resolvers from './resolvers';
import { NAMESPACE } from '../constants'

export function registerBaseObjectStore ( endpoint, options ) {
	const storeName = NAMESPACE + '/' + endpoint;
	const storeSelectors = select( storeName );
	if ( ! storeSelectors ){
		registerStore( storeName, {
			reducer,
			actions,
			controls,
			selectors,
			resolvers
		} );
	}

}

// registerBaseObjectStore( 'contracts' );
// select( 'gh/v4/contracts' ).dispatch(...)