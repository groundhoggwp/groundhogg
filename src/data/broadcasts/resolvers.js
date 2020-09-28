/**
 * Internal dependencies
 */
import { receiveBroadcasts, setRequestingError } from './actions';
import { NAMESPACE } from '../constants';

/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Request all tags.
 */
export function* getBroadcasts() {
	try {
		const url = NAMESPACE + '/broadcasts/';
		const result = yield apiFetch( {
			path: url,
			method: 'GET',
		} );
		yield receiveBroadcasts( result );
	} catch ( error ) {
		yield setRequestingError( error );
	}
}