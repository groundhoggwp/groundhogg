/**
 * Internal dependencies
 */
import { getResourceName } from '../utils';

/**
 * For potential use in our base object
 * @param {*} state
 * @param {*} itemType
 * @param {*} query
 */
export const getItems = ( state, itemType, query ) => {
	const resourceName = getResourceName( itemType, query );
	const ids =
		( state.items[ resourceName ] && state.items[ resourceName ].data ) ||
		[];
	return ids.reduce( ( map, id ) => {
		map.set( id, state.data[ itemType ][ id ] );
		return map;
	}, new Map() );
};

export const getContacts = ( state ) => {
	return state.items || [];
}

export const getContact = ( state, id ) => {
	return getItems(state).find(item => item.ID === id);
}

export const getItemsTotalCount = ( state, itemType, query ) => {
	const resourceName = getResourceName( itemType, query );
	return (
		( state.items[ resourceName ] &&
			state.items[ resourceName ].totalCount ) ||
		0
	);
};

export const getItemsError = ( state, itemType, query ) => {
	const resourceName = getResourceName( itemType, query );
	return state.errors[ resourceName ];
};
