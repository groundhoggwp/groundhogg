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

export default (endpoint) => ( {

  __endpoint: endpoint,

  * getItems (query) {
    yield setIsRequestingItems(true)
    try {
      const url = addQueryArgs(`${endpoint}`, query)
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
  },

  * getItem (item) {
    yield setIsRequestingItems(true)

    try {
      const url = `${endpoint}/${ item }`
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
  },
} )