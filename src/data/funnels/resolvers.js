/**
 * Internal dependencies
 */
import { receiveFunnels, setRequestingError, setIsRequestingFunnels } from './actions';
import { NAMESPACE } from '../constants';

/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Request all tags.
 */
export function* getFunnels() {
	yield setIsRequestingFunnels( true );
	try {
		const url = NAMESPACE + '/funnels/';
		const result = yield apiFetch( {
			path: url,
			method: 'GET',
		} );
		yield setIsRequestingFunnels( false );
		yield receiveFunnels( result );
	} catch ( error ) {
		yield setRequestingError( error );
	}
}