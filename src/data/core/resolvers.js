/**
 * External dependencies
 */
import { includes, get, hasIn, compact } from 'lodash';

/**
 * WordPress dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import {
	receiveUserPermission,
} from './actions';

/**
 * Checks whether the current user can perform the given action on the given
 * REST resource.
 *
 * @param {string}  action   Action to check. One of: 'create', 'read', 'update',
 *                           'delete'.
 * @param {string}  resource REST resource to check, e.g. 'funnels' or 'emails'.
 * @param {?string} id       ID of the rest resource to check.
 */
export function* canUser( action, resource, id ) {
	const methods = {
		create: 'POST',
		read: 'GET',
		update: 'PUT',
		delete: 'DELETE',
	};

	const method = methods[ action ];

	if ( ! method ) {
		throw new Error( `'${ action }' is not a valid action.` );
	}

	const path = id ? `/gh/v4/${ resource }/${ id }` : `/gh/v4/${ resource }`;

	let response;
	try {
		response = yield apiFetch( {
			path,
			method: 'OPTIONS',
			parse: false,
		} );
	} catch ( error ) {
		console.log(error);
		// Do nothing if our OPTIONS request comes back with an API error (4xx or
		// 5xx). The previously determined isAllowed value will remain in the store.
		return;
	}

	let allowHeader;
	if ( hasIn( response, [ 'headers', 'get' ] ) ) {
		// If the request is fetched using the fetch api, the header can be
		// retrieved using the 'get' method.
		allowHeader = response.headers.get( 'allow' );
	} else {
		// If the request was preloaded server-side and is returned by the
		// preloading middleware, the header will be a simple property.
		allowHeader = get( response, [ 'headers', 'Allow' ], '' );
	}
	const key = compact( [ action, resource, id ] ).join( '/' );
	const isAllowed = includes( allowHeader, method );

	yield receiveUserPermission( key, isAllowed );
}
