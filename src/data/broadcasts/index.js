/**
 * Internal dependencies
 */
import {
  registerBaseObjectStore,
  getStoreName
} from '../base-object';

import * as actions from './actions';

const STORE_NAME = 'broadcasts';

registerBaseObjectStore( STORE_NAME , {
  actions : actions
});

export const BROADCASTS_STORE_NAME = getStoreName( STORE_NAME );
