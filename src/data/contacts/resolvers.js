/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { NAMESPACE } from '../constants';
import { setError, setItems } from './actions';
import { fetchWithHeaders } from '../controls';

export function* getContacts( query ) {
	const endpoint = 'contacts';
	try {
		const url = addQueryArgs( `${ NAMESPACE }/${ endpoint }`, query );
		const response = yield fetchWithHeaders( {
			path: url,
			method: 'GET',
		} );

		const totalCount = parseInt(
			response.headers.get( 'x-wp-total' ),
			10
		);
		yield setItems( itemType, query, response.data, totalCount );
	} catch ( error ) {
		yield setError( query, error );
	}
}