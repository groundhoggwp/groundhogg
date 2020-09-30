/**
 * Internal dependencies
 */
import { receiveTags, setRequestingError, setIsRequestingTags } from './actions';
import { NAMESPACE } from '../constants';

/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Request all tags.
 */
export function* getTags() {
	yield setIsRequestingTags( true );
	try {
		const url = NAMESPACE + '/tags/';
		const result = yield apiFetch( {
			path: url,
			method: 'GET',
		} );
		yield setIsRequestingTags( false );
		yield receiveTags( result );
	} catch ( error ) {
		yield setRequestingError( error );
	}
}