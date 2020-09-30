/**
 * Internal dependencies
 */
import { getResourceName } from '../utils';

export const getEmails = ( state, itemType, query ) => {
	const resourceName = getResourceName( itemType, query );
	const ids =
		( state.items[ resourceName ] && state.items[ resourceName ].data ) ||
		[];
	return ids.reduce( ( map, id ) => {
		map.set( id, state.data[ itemType ][ id ] );
		return map;
	}, new Map() );
};
