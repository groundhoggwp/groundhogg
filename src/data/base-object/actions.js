/**
 * Internal dependencies
 */
import TYPES from './action-types'
import { addNotification } from '../../utils'

/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls'
import { addQueryArgs } from '@wordpress/url'
import { __ } from '@wordpress/i18n'

function receiveItems (items) {
  return {
    type: TYPES.RECEIVE_ITEMS,
    items,
  }
}

function receiveItem (item) {
  return {
    type: TYPES.RECEIVE_ITEM,
    item,
  }
}

function setIsCreatingItems (isCreating) {

  return {
    type: TYPES.SET_IS_CREATING,
    isCreating,
  }
}

function setCreatingError (error) {
  return {
    type: TYPES.SET_CREATING_ERROR,
    error,
  }
}

function setIsRequestingItems (isRequesting) {
  return {
    type: TYPES.SET_IS_REQUESTING,
    isRequesting,
  }
}

function setRequestingError (error) {
  return {
    type: TYPES.SET_REQUESTING_ERROR,
    error,
  }
}

function setIsUpdatingItems (isUpdating) {
  if ( ! isUpdating ) {
    addNotification( {
      message : __( 'Item successfully updated.' )
    } );
  }

  return {
    type: TYPES.SET_IS_UPDATING,
    isUpdating,
  }
}

function setUpdatingError (error) {
  return {
    type: TYPES.SET_UPDATING_ERROR,
    error,
  }
}

function setIsDeletingItems (isDeleting) {
  if ( ! isDeleting ) {
    addNotification( {
      message : __( 'Item successfully deleted.', 'info' ),
    } );
  }
  return {
    type: TYPES.SET_IS_DELETING,
    isDeleting,
  }
}

function setDeletingError (error) {

  return {
    type: TYPES.SET_DELETING_ERROR,
    error,
  }
}

export default (endpoint) => ( {

  endpoint,
	receiveItems,
	receiveItem,
	setIsRequestingItems,
	setRequestingError,

  * fetchItems ( query ){
    yield setIsRequestingItems(true)

    try {
      const result = yield apiFetch({
        // path: '/gh/v4/reports?start=2019-10-06&end=2020-10-06',
        path: addQueryArgs( `${ endpoint }`, query ),
      })

      yield setIsRequestingItems(false)
      yield {
        type: TYPES.RECEIVE_ITEMS,
        items: result.items,
        totalItems: result.total_items
      }
    }
    catch (e) {
      yield setCreatingError(e)
    }
  },

  * createItems (items) {
    yield setIsCreatingItems(true)

    try {
      const result = yield apiFetch({
        method: 'POST',
        path: `${ endpoint }`,
        data: items,
      })

      yield setIsCreatingItems(false)
      yield {
        type: TYPES.CREATE_ITEMS,
        items: result.items,
      }
    }
    catch (e) {
      yield setCreatingError(e)
    }
  },

  * createItem (item) {
    yield setIsCreatingItems(true)

    try {
      const result = yield apiFetch({
        method: 'POST',
        path: `${ endpoint }`,
        data: item,
      })

      yield setIsCreatingItems(false)
      yield {
        type: TYPES.CREATE_ITEM,
        item: result.item,
      }
    }
    catch (e) {
      yield setCreatingError(e)
    }
  },

  * updateItems (items) {
    yield setIsUpdatingItems(true)

    try {
      const response = yield apiFetch({
        method: 'PATCH',
        path: `${ endpoint }`,
        data: items,
      })

      yield setIsUpdatingItems(false)
      yield {
        type: TYPES.UPDATE_ITEMS,
        items: response.items,
      }
    }
    catch (e) {
      yield setUpdatingError(e)
    }
  },

  * updateItem (itemId, data) {

    yield setIsUpdatingItems(true)

    try {
      const response = yield apiFetch({
        method: 'PATCH',
        path: `${ endpoint }/${ itemId }`,
        data: data,
      })

      yield setIsUpdatingItems(false)
      yield {
        type: TYPES.UPDATE_ITEM,
        item: response.item,
      }
    }
    catch (e) {
      yield setUpdatingError(e)
    }
  },

  * deleteItems (itemIds) {
    yield setIsDeletingItems(true)

    try {
      yield apiFetch({
        method: 'DELETE',
        path: `${ endpoint }`,
        data: itemIds,
      })

      yield setIsDeletingItems(false)
      yield {
        type: TYPES.DELETE_ITEMS,
        itemIds,
      }
    }
    catch (e) {
      yield setDeletingError(e)
    }
  },

  * deleteItem (itemId) {
    yield setIsDeletingItems(true)

    try {
      yield apiFetch({
        path: `${ endpoint }/${ itemId }`,
        method: 'DELETE',
      })

      yield setIsDeletingItems(false)
      yield {
        type: TYPES.DELETE_ITEM,
        itemId,
      }
    }
    catch (e) {
      yield setDeletingError(e)
    }
  },

} )
