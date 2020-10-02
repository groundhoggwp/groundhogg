/**
 * Internal dependencies
 */
import { receiveTags, setRequestingError } from './actions';
import { NAMESPACE } from '../constants';

/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Request all tags.
 */
export function* getEmails() {
	try {
		const url = NAMESPACE + '/emails/';
		const result = yield apiFetch( {
			path: url,
			method: 'GET',
		} );
		yield receiveTags( result );
	} catch ( error ) {
		yield setRequestingError( error );
	}
}
