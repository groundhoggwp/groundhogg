/**
 * Internal dependencies
 */
import {
  registerBaseObjectStore,
  getStoreName
} from '../base-object';

const STORE_NAME = 'funnels';

registerBaseObjectStore( STORE_NAME );

export const FUNNELS_STORE_NAME = getStoreName( STORE_NAME );
