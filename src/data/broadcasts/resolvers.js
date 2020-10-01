/**
 * Internal dependencies
 */
import { receiveBroadcasts, setRequestingError ,setIsRequestingBroadcasts } from './actions';
import { NAMESPACE } from '../constants';

/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';
import {setIsRequestingTags} from "../tags/actions";

/**
 * Request all tags.
 */
export function* getBroadcasts() {
	yield setIsRequestingBroadcasts( true );
	try {
		const url = NAMESPACE + '/broadcasts/';
		const result = yield apiFetch( {
			path: url,
			method: 'GET',
		} );
		yield setIsRequestingTags( false );
		yield receiveBroadcasts( result );
	} catch ( error ) {
		yield setRequestingError( error );
	}
}