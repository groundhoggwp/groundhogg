/**
 * Internal dependencies
 */
import {
  registerBaseObjectStore,
  getStoreName
} from '../base-object';

const STORE_NAME = 'notes';

registerBaseObjectStore( STORE_NAME );

export const NOTES_STORE_NAME = getStoreName( STORE_NAME );
