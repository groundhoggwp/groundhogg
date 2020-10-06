/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { QUERY_DEFAULTS } from '../constants';

export function requestContactTags( itemId ) {
	return {
		type: TYPES.REQUEST_CONTACT_TAGS,
		itemId
	};
}

export function addContactTags( itemId, tags ) {
	return {
		type: TYPES.ADD_CONTACT_TAGS,
		itemId,
		tags
	};
}

export function deleteContactTags( itemId, tags ) {
	return {
		type: TYPES.DELETE_CONTACT_TAGS,
		itemId,
		tags
	};
}

export function requestContactFiles( itemId ) {
	return {
		type: TYPES.REQUEST_CONTACT_FILES,
		itemId
	};
}

export function addContactFiles( itemId, files ) {
	return {
		type: TYPES.ADD_CONTACT_FILES,
		itemId,
		files
	};
}

export function deleteContactFiles( itemId, files ) {
	return {
		type: TYPES.DELETE_CONTACT_FILES,
		itemId,
		files
	};
}

export function mergeContacts( itemId, others ) {
	return {
		type: TYPES.MERGE_CONTACTS,
		itemId,
		others
	};
}

export function setIsRequestingTags( isRequesting ) {
	return {
		type: TYPES.SET_IS_REQUESTING_TAGS,
		isRequesting
	};
}

export function setRequestingTagsError( error ) {
	return {
		type: TYPES.SET_REQUESTING_TAGS_ERROR,
		error
	};
}

export function setIsDeletingTags( isDeleting ) {
	return {
		type: TYPES.SET_IS_DELETING_FILES,
		isDeleting
	};
}

export function setDeletingTagsError( error ) {
	return {
		type: TYPES.SET_DELETING_TAGS_ERROR,
		error
	};
}

export function setIsAddingTags( isAdding ) {
	return {
		type: TYPES.SET_IS_ADDING_TAGS,
		isAdding
	};
}

export function setAddingTagsError( error ) {
	return {
		type: TYPES.SET_ADDING_TAGS_ERROR,
		error
	};
}

export function setIsRequestingFiles( isRequesting ) {
	return {
		type: TYPES.SET_IS_REQUESTING_FILES,
		isRequesting
	};
}

export function setRequestingFilesError( error ) {
	return {
		type: TYPES.SET_REQUESTING_FILES_ERROR,
		error
	};
}

export function setIsDeletingFiles( isDeleting ) {
	return {
		type: TYPES.SET_IS_DELETING_FILES,
		isDeleting
	};
}

export function setDeletingFilesError( error ) {
	return {
		type: TYPES.SET_DELETING_FILES_ERROR,
		error
	};
}

export function setIsAddingFiles( isAdding ) {
	return {
		type: TYPES.SET_IS_ADDING_FILES,
		isAdding
	};
}

export function setAddingFilesError( error ) {
	return {
		type: TYPES.SET_ADDING_FILES_ERROR,
		error
	};
}

export function setIsMerging( isMerging ) {
	return {
		type: TYPES.SET_IS_MERGING,
		isMerging
	};
}

export function setMergingError( error ) {
	return {
		type: TYPES.SET_MERGING_ERROR,
		error
	};
}

export function showContactFilters() {
	return {
		type: TYPES.SHOW_CONTACT_FILTERS,
	};
}

export function changeContext( context ) {
	return {
		type: TYPES.CHANGE_CONTEXT,
		context
	};
}

export function changeQuery( queryVars ) {
	return {
		type: TYPES.CHANGE_QUERY,
		queryVars
	};
}

export function clearItems() {
	return {
		type: TYPES.CLEAR_ITEMS,
	};
}

export function clearState() {
	return {
		type: TYPES.CLEAR_STATE,
	};
}

export function filterContacts() {
	return {
		type: TYPES.FILTER_CONTACTS,
	};
}

export function resetQuery( queryVars = QUERY_DEFAULTS ) {
	return {
		type: TYPES.CHANGE_QUERY,
		queryVars
	};
}