/**
 * Internal dependencies
 */
import BaseActions from './actions';
import reducer from './reducer';
import * as selectors from './selectors';

import {
	registerBaseObjectStore,
	getStoreName
} from '../base-object';
import broadcastsReducer from 'data/broadcasts/reducer'

const STORE_NAME = 'contacts';


const actions = new BaseActions( STORE_NAME );

registerBaseObjectStore( STORE_NAME, {
	reducer : reducer,
	actions : actions,
	selectors : selectors
} );

export const CONTACTS_STORE_NAME = getStoreName( STORE_NAME );