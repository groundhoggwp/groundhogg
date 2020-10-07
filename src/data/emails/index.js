/**
 * Internal dependencies
 */
import {
	registerBaseObjectStore,
	getStoreName
} from '../base-object';

const STORE_NAME = 'emails';

registerBaseObjectStore( STORE_NAME );

export const EMAILS_STORE_NAME = getStoreName( STORE_NAME );
