/**
 * Internal dependencies
 */
import {receiveEvents, setIsRequestingEvents, setRequestingError} from './actions';
import { NAMESPACE } from '../constants';

/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';


/**
 * Request all the Events
 */
export function* getEvents( view = 'failed' ) {

	yield setIsRequestingEvents( true );
	// manage getting views
	try {
		const url = NAMESPACE + '/events?view='+view;
		const result = yield apiFetch( {
			path: url,
			method: 'GET',
		} );
		yield setIsRequestingEvents( false );
		yield receiveEvents( result );
	} catch ( error ) {
		yield setRequestingError( error );
	}
}