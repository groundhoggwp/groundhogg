/**
 * Internal dependencies
 */
import TYPES from './action-types'
import { NAMESPACE, QUERY_DEFAULTS } from '../constants'
import { apiFetch } from '@wordpress/data-controls'
import { addQueryArgs } from '@wordpress/url'

export function requestContactTags (itemId) {
  return {
    type: TYPES.REQUEST_CONTACT_TAGS,
    itemId
  }
}

export function addContactTags (itemId, tags) {
  return {
    type: TYPES.ADD_CONTACT_TAGS,
    itemId,
    tags
  }
}

export function deleteContactTags (itemId, tags) {
  return {
    type: TYPES.DELETE_CONTACT_TAGS,
    itemId,
    tags
  }
}

export function addContactFiles (itemId, files) {
  return {
    type: TYPES.ADD_CONTACT_FILES,
    itemId,
    files
  }
}

export function deleteContactFiles (itemId, files) {
  return {
    type: TYPES.DELETE_CONTACT_FILES,
    itemId,
    files
  }
}

export function mergeContacts (itemId, others) {
  return {
    type: TYPES.MERGE_CONTACTS,
    itemId,
    others
  }
}

export function setIsRequestingTags (isRequesting) {
  return {
    type: TYPES.SET_IS_REQUESTING_TAGS,
    isRequesting
  }
}

export function setRequestingTagsError (error) {
  return {
    type: TYPES.SET_REQUESTING_TAGS_ERROR,
    error
  }
}

export function setIsDeletingTags (isDeleting) {
  return {
    type: TYPES.SET_IS_DELETING_FILES,
    isDeleting
  }
}

export function setDeletingTagsError (error) {
  return {
    type: TYPES.SET_DELETING_TAGS_ERROR,
    error
  }
}

export function setIsAddingTags (isAdding) {
  return {
    type: TYPES.SET_IS_ADDING_TAGS,
    isAdding
  }
}

export function setAddingTagsError (error) {
  return {
    type: TYPES.SET_ADDING_TAGS_ERROR,
    error
  }
}

export function setIsRequestingFiles (isRequesting) {
  return {
    type: TYPES.SET_IS_REQUESTING_FILES,
    isRequesting
  }
}

export function setRequestingFilesError (error) {
  return {
    type: TYPES.SET_REQUESTING_FILES_ERROR,
    error
  }
}

export function setIsDeletingFiles (isDeleting) {
  return {
    type: TYPES.SET_IS_DELETING_FILES,
    isDeleting
  }
}

export function setDeletingFilesError (error) {
  return {
    type: TYPES.SET_DELETING_FILES_ERROR,
    error
  }
}

export function setIsAddingFiles (isAdding) {
  return {
    type: TYPES.SET_IS_ADDING_FILES,
    isAdding
  }
}

export function setAddingFilesError (error) {
  return {
    type: TYPES.SET_ADDING_FILES_ERROR,
    error
  }
}

export function setIsMerging (isMerging) {
  return {
    type: TYPES.SET_IS_MERGING,
    isMerging
  }
}

export function setMergingError (error) {
  return {
    type: TYPES.SET_MERGING_ERROR,
    error
  }
}

export function showContactFilters () {
  return {
    type: TYPES.SHOW_CONTACT_FILTERS,
  }
}

export function changeContext (context) {
  return {
    type: TYPES.CHANGE_CONTEXT,
    context
  }
}

export function changeQuery (queryVars) {
  return {
    type: TYPES.CHANGE_QUERY,
    queryVars
  }
}

export function clearItems () {
  return {
    type: TYPES.CLEAR_ITEMS,
  }
}

export function clearState () {
  return {
    type: TYPES.CLEAR_STATE,
  }
}

export function filterContacts () {
  return {
    type: TYPES.FILTER_CONTACTS,
  }
}

export function resetQuery (queryVars = QUERY_DEFAULTS) {
  return {
    type: TYPES.CHANGE_QUERY,
    queryVars
  }
}

// TODO build methods and do stuff

export default (endpoint) => ({
  endpoint,
  * addFile (contactId, data) {
    yield setIsAddingFiles(true)
    try {
      const result = yield apiFetch({
        method: 'POST',
        path: `${NAMESPACE}/${endpoint}/${contactId}/files`,
        body: data
      })
      yield  setIsAddingFiles(false)
      return { success: true, ...result }
    } catch (e) {
      // yield setSchedulingError(e);
      return { success: false, e }
    }
  },
  * fetchFiles (contactId, query) {
    yield setIsRequestingFiles(true)
    try {
      const result = yield apiFetch({
        path: addQueryArgs(`${NAMESPACE}/${endpoint}/${contactId}/files`, query),
      })

      yield setIsRequestingFiles(false)
      yield {
        type: TYPES.RECEIVE_CONTACT_FILES,
        files: result.items,
        totalFiles: result.total_items
      }
    } catch (e) {
      yield setRequestingFilesError(e)
    }
  },
  * deleteFiles (contactId, itemIds) {
    yield setIsDeletingFiles(true)
    try {
      yield apiFetch({
        method: 'DELETE',
        path: `${NAMESPACE}/${endpoint}/${contactId}/files`,
        data: itemIds,
      })
      yield setIsDeletingFiles(false)
      yield this.fetchFiles(contactId)
    } catch (e) {
      yield setDeletingError(e)
    }
  },

})