/**
 * Internal dependencies
 */
import { receiveEvents, setRequestingError } from './actions';
import { NAMESPACE } from '../constants';

/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Request all the Events
 */
export function* getEvents() {

	// manage getting views
	try {
		const url = NAMESPACE + '/events';
		const result = yield apiFetch( {
			path: url,
			method: 'GET',
		} );
		yield receiveEvents( result );
	} catch ( error ) {
		yield setRequestingError( error );
	}
}