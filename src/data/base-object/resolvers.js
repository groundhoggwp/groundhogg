/**
 * Internal dependencies
 */
import {
  setRequestingError,
  setIsUpdating,
  setUpdatingError,
  setIsRequestingItems,
  receiveItems,
  receiveItem,
} from './actions'

/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls'
import { addQueryArgs } from '@wordpress/url'

/**
 * Create a Base Resovler
 *
 * @param endpoint
 * @constructor
 */
export default function BaseResolver( endpoint ){

  const _self = this;

  this.__endpoint = endpoint;

  /**
   * Request all tags.
   */
  this.getItems = function * (query) {

    yield setIsRequestingItems(true)
    try {
      const url = addQueryArgs(`${ _self.__endpoint }`, query)
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
  this.getItem = function * (item) {
    yield setIsRequestingItems(true)

    try {
      const url = `${ _self.__endpoint }/${ item }`
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
}