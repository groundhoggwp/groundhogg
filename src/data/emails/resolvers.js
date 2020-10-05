/**
 * Internal dependencies
 */
import { receiveEmails, setRequestingError, setIsRequestingEmails } from './actions';
import { NAMESPACE } from '../constants';

/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Request all Emails.
 */
export function* getEmails() {
	yield setIsRequestingEmails( true );
	try {
		const url = NAMESPACE + '/emails/';
		const result = yield apiFetch( {
			path: url,
			method: 'GET',
		} );

		yield setIsRequestingEmails( false );
		yield receiveEmails( result.items );
	} catch ( error ) {
		yield setRequestingError( error );
	}
}
