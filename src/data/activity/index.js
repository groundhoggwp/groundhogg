/**
 * Internal dependencies
 */
import {
	registerBaseObjectStore,
	getStoreName
} from '../base-object';

const STORE_NAME = 'activity';

registerBaseObjectStore( STORE_NAME );

export const ACTIVITY_STORE_NAME = getStoreName( STORE_NAME );
