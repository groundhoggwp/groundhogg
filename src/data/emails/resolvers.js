/**
 * Internal dependencies
 */
import { receiveEmails, setRequestingError } from './actions';
import { NAMESPACE } from '../constants';

/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Request all Emails.
 */
export function* getEmails() {
	try {
		const url = NAMESPACE + '/emails/';
		const result = yield apiFetch( {
			path: url,
			method: 'GET',
		} );

		yield receiveEmails( result );
	} catch ( error ) {
		yield setRequestingError( error );
	}
}

// /**
//  * Request all tags.
//  */
// export function* getTags() {
// 	try {
// 		const url = NAMESPACE + '/tags/';
// 		const result = yield apiFetch( {
// 			path: url,
// 			method: 'GET',
// 		} );
// 		yield receiveTags( result );
// 	} catch ( error ) {
// 		yield setRequestingError( error );
// 	}
// }
