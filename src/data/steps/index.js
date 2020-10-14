/**
 * Internal dependencies
 */
import {
  registerBaseObjectStore,
  getStoreName
} from '../base-object';

const STORE_NAME = 'steps';

registerBaseObjectStore( STORE_NAME );

export const STEPS_STORE_NAME = getStoreName( STORE_NAME );
