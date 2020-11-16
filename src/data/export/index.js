/**
 * Internal dependencies
 */
import {
	registerBaseObjectStore,
	getStoreName
} from '../base-object';

const STORE_NAME = 'files/exports';


registerBaseObjectStore( STORE_NAME );

export const EXPORT_STORE_NAME = getStoreName( STORE_NAME );
