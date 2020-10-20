/**
 * Internal dependencies
 */
import TYPES from './action-types'
import BaseActions from '../base-object/actions';

// import { addNotification } from '../../utils'

/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls'

export default (endpoint) => ( {

  endpoint,
  * createStep (items) {
    // yield setIsCreatingItems(true)

    try {
      const result = yield apiFetch({
        method: 'POST',
        path: `${ endpoint }`,
        data: items,
      })

      // yield setIsCreatingItems(false)
      yield {
        type: TYPES.CREATE_ITEMS,
        items: result.items,
      }
    }
    catch (e) {
      // yield setCreatingError(e)
    }
  },

} )
