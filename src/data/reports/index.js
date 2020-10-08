/**
 * Internal dependencies
 */
import {
	registerBaseObjectStore,
	getStoreName
} from '../base-object';

const STORE_NAME = 'reports';

registerBaseObjectStore( STORE_NAME );

export const REPORTS_STORE_NAME = getStoreName( STORE_NAME );
