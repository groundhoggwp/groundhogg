/**
 * Internal dependencies
 */
import {
	registerBaseObjectStore,
	getStoreName
} from '../base-object';

const STORE_NAME = 'tags';

registerBaseObjectStore( STORE_NAME );

export const TAGS_STORE_NAME = getStoreName( STORE_NAME );
