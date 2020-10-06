/**
 * Internal dependencies
 */
import {
  setRequestingError,
  setIsUpdating,
  setUpdatingError,
  setIsRequestingItems,
  receiveItems, receiveItem,
} from './actions'
import { NAMESPACE } from '../constants'

/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls'
import { addQueryArgs } from '@wordpress/url'

/**
 * Request all tags.
 */
export function * getItems (query) {
  yield setIsRequestingItems(true)
  try {
    const url = addQueryArgs(`${ NAMESPACE }/${ endpoint }`, query)
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
 * Request all tags.
 */
export function * getItem (item) {
  yield setIsRequestingItems(true)
  try {
    const url = `${ NAMESPACE }/${ endpoint }/${ item }`
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