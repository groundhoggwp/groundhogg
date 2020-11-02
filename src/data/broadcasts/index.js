/**
 * Internal dependencies
 */
import {
  registerBaseObjectStore,
  getStoreName
} from '../base-object';

import BaseActions from './actions';
import broadcastsReducer from './reducer';
import * as selectors from './selectors';

const STORE_NAME = 'broadcasts';

const actions = new BaseActions( STORE_NAME );

registerBaseObjectStore( STORE_NAME , {
  reducer : broadcastsReducer,
  actions : actions,
  selectors : selectors
});

export const BROADCASTS_STORE_NAME = getStoreName( STORE_NAME );
