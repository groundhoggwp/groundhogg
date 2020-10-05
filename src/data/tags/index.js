/**
 * External dependencies
 */
import { registerStore } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';
import * as selectors from './selectors';
import * as actions from './actions';
import controls from '../controls';
import reducer from './reducer';
import * as resolvers from './resolvers';

registerStore( STORE_NAME, {
	reducer,
	actions,
	controls,
	selectors,
	resolvers
} );

export const TAGS_STORE_NAME = STORE_NAME;
