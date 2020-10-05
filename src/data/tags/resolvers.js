/**
 * Internal dependencies
 */
import {
	receiveTags,
	receiveTag,
	setRequestingError,
	setIsRequestingTags
} from './actions';
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
		yield receiveTags( result.items );
	} catch ( error ) {
		yield setRequestingError( error );
	}
}

/**
 * Request all tags.
 */
export function* getTag( item ) {
	yield setIsRequestingTags( true );
	try {
		const url = `${NAMESPACE}/tags/${item}`;
		const result = yield apiFetch( {
			path: url,
			method: 'GET',
		} );

		yield setIsRequestingTags( false );
		yield receiveTag( result.item );
	} catch ( error ) {
		yield setRequestingError( error );
	}
}