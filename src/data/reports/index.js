/**
 * Internal dependencies
 */
import {
	registerBaseObjectStore,
	getStoreName
} from '../base-object';
import { INITIAL_STATE } from 'data/base-object/constants'

const STORE_NAME = 'reports';

registerBaseObjectStore( STORE_NAME, {
	initialState: {
		...INITIAL_STATE,
		useCache: false
	}
} );

export const REPORTS_STORE_NAME = getStoreName( STORE_NAME );
