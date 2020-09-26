/**
 * Internal dependencies
 */
import { receiveTags, setRequestingError } from './actions';
import { batchFetch } from './controls';

/**
 * Request a tag value.
 *
 * @param {string} name - Tag name
 */
export function* getTag( name ) {
	try {
		const result = yield batchFetch( name );
		yield receiveTags( result );
	} catch ( error ) {
		yield setRequestingError( error, name );
	}
}

/**
 * Request all tags.
 */
export function* getTags() {
	try {
		const result = yield batchFetch();
		yield receiveTags( result );
	} catch ( error ) {
		yield setRequestingError( error, name );
	}
}