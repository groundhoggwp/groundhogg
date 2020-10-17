/**
 * Internal dependencies
 */
import { getResourceName } from '../utils';
import TYPES from './action-types';

function getAllReports (reports) {
  return {
    type: TYPES.FETCH_ALL_REPORTS,
    reports,
  }
}

export default (endpoint) => ( {

  endpoint,
	getAllReports,
	// receiveItem,
	// setIsRequestingItems,
	// setRequestingError,

  * getAllReports ( query ){
    // yield setIsRequestingItems(true)

    try {
      const result = yield apiFetch({
        path: '/gh/v4/reports?start=2019-10-06&end=2020-10-06',
        // path: addQueryArgs( `${ endpoint }`, query ),
      })

			console.log('inside result', result)

      // yield setIsRequestingItems(false)
      yield {
        type: TYPES.FETCH_ALL_REPORTS,
        items: result.reports,
        // totalItems: result.total_items
      }
    }
    catch (e) {
      // yield setCreatingError(e)
    }
  },
} )
