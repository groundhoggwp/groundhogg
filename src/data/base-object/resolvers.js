/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls'
import { addQueryArgs } from '@wordpress/url'

export default (endpoint, actions) => ( {

  __endpoint: endpoint,

  * getItems (query) {
    yield actions.setIsRequestingItems(true)
    try {
      const url = addQueryArgs(`${ endpoint }`, query)
      const result = yield apiFetch({
        path: url,
        method: 'GET',
      })

      yield actions.setIsRequestingItems(false)
      yield actions.receiveItems(result.items)
    }
    catch (error) {
      yield actions.setRequestingError(error)
    }
  },

  * getItem (item) {
    yield actions.setIsRequestingItems(true)

    try {
      const url = `${ endpoint }/${ item }`
      const result = yield apiFetch({
        path: url,
        method: 'GET',
      })

      yield actions.setIsRequestingItems(false)
      yield actions.receiveItem(result.item)
    }
    catch (error) {
      yield actions.setRequestingError(error)
    }
  },
} )