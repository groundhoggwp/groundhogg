/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls'

export default (endpoint, actions) => ( {

  __endpoint: endpoint,

  * getItems () {
    yield actions.fetchItems()
  },

  * getItem (itemId) {
    yield actions.setIsRequestingItems(true)

    try {
      const url = `${ endpoint }/${ itemId }`
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