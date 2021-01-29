// /**
//  * External dependencies
//  */
//
// import { select, registerStore } from '@wordpress/data';
// import { controls } from '@wordpress/data-controls';
//
// /**
//  * Internal dependencies
//  */
// import { STORE_NAME } from './constants';
// import * as selectors from './selectors';
// import * as actions from './actions';
// import * as resolvers from './resolvers';
// import reducer from './reducer';
//
// const storeSelectors = select( STORE_NAME );
//
// if ( ! storeSelectors ) {
// 	registerStore( STORE_NAME, {
// 		reducer,
// 		actions,
// 		controls,
// 		selectors,
// 		resolvers,
// 	} );
// }
//
// export const IMPORT_STORE_NAME = STORE_NAME;


/**
 * Internal dependencies
 */
import {
	registerBaseObjectStore,
	getStoreName
} from '../base-object';

const STORE_NAME = 'files/imports';


registerBaseObjectStore( STORE_NAME );

export const IMPORT_STORE_NAME = getStoreName( STORE_NAME );
