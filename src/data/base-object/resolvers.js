/**
 * Internal dependencies
 */
import {
  createItems,
  createItem,
  setIsCreatingItems,
  setCreatingError,
  receiveItems,
  receiveItem,
  setIsRequestingItems,
  setRequestingError,
  updateItems,
  updateItem,
  setIsUpdatingItems,
  setUpdatingError,
  deleteItems,
  deleteItem,
  setIsDeletingItems,
  setDeletingError,
} from './actions'

/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls'
import { addQueryArgs } from '@wordpress/url'

/**
 * Create multiple items.
 */
export function * createItems (items) {
  const endpoint = getEndpoint();
  yield setIsCreatingItems(true)
  try {
    const url = addQueryArgs(`${ endpoint }`)
    const result = yield apiFetch({
      path: url,
      data: items,
      method: 'POST',
    })

    yield setIsCreatingItems(false)
    yield createItems(result.items)
  }
  catch (error) {
    yield setCreatingError(error)
  }
}

/**
 * Create an item.
 */
export function * createItem (item) {
  yield createItems( [ item ] );
}

/**
 * Request all items.
 */
export function * getItems (query) {
  const endpoint = getEndpoint();
  yield setIsRequestingItems(true)
  try {
    const url = addQueryArgs(`${ endpoint }`, query)
    const result = yield apiFetch({
      path: url,
      method: 'GET',
    })

    yield setIsRequestingItems(false)
    yield receiveItems(result.items)
  }
  catch (error) {
    yield setRequestingError(error)
  }
}

/**
 * Request an item.
 */
export function * getItem (itemId) {
  yield setIsRequestingItems(true)

  const endpoint = getEndpoint();

  try {
    const url = `${ endpoint }/${ itemId }`
    const result = yield apiFetch({
      path: url,
      method: 'GET',
    })

    yield setIsRequestingItems(false)
    yield receiveItem(result.item)
  }
  catch (error) {
    yield setRequestingError(error)
  }
}

/**
 * Update multiple items.
 */
export function * updateItems (items) {
  const endpoint = getEndpoint();
  yield setIsUpdatingItems(true)
  try {
    const url = addQueryArgs(`${ endpoint }`, query)
    const result = yield apiFetch({
      path: url,
      data: items,
      method: 'PATCH',
    })

    yield setIsUpdatingItems(false)
    yield updateItems(result.items)
  }
  catch (error) {
    yield setUpdatingError(error)
  }
}

/**
 * Update an item.
 */
export function * updateItem (item) {
  yield setIsUpdatingItems(true)

  const endpoint = getEndpoint();

  try {
    const url = `${ endpoint }/${ item.ID }`
    const result = yield apiFetch({
      path: url,
      data: item,
      method: 'PATCH',
    })

    yield setIsUpdatingItems(false)
    yield updateItem(result.item)
  }
  catch (error) {
    yield setUpdatingError(error)
  }
}

/**
 * Delete multiple items.
 */
export function * deleteItems (itemIds) {
  const endpoint = getEndpoint();
  yield setIsDeletingItems(true)
  try {
    const url = addQueryArgs(`${ endpoint }`, query)
    const result = yield apiFetch({
      path: url,
      data: itemIds,
      method: 'DELETE',
    })

    yield setIsDeletingItems(false)
    yield deleteItems(result.items)
  }
  catch (error) {
    yield setDeletingError(error)
  }
}

/**
 * Delete an item.
 */
export function * deleteItem (itemId) {
  yield setIsDeletingItems(true)

  const endpoint = getEndpoint();

  try {
    const url = `${ endpoint }/${ itemId }`
    const result = yield apiFetch({
      path: url,
      method: 'DELETE',
    })

    yield setIsDeletingItems(false)
    yield deleteItem(result.item)
  }
  catch (error) {
    yield setDeletingError(error)
  }
}

/**
 * This is overridden
 */
export function getEndpoint() {}