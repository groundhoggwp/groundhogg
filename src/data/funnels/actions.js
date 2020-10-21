/**
 * Internal dependencies
 */
import TYPES from './action-types'
import BaseActions from '../base-object/actions';
import { NAMESPACE } from '../constants';
// import { addNotification } from '../../utils'

/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls'

function setIsCreatingStep (isCreating) {
  return {
    type: TYPES.SET_IS_CREATING,
    isCreating,
  }
}

function setCreatingStepError (error) {
  return {
    type: TYPES.SET_CREATING_ERROR,
    error,
  }
}

export default (endpoint) => ( {
  endpoint,
  setIsCreatingStep,
  setCreatingStepError,
  * createStep (data, funnelID) {
    yield setIsCreatingStep(false)

    try {
      const result = yield apiFetch({
        method: 'POST',
        path: `${NAMESPACE}/${ endpoint }/${funnelID}/step/`,
        data
      })

      console.log('data', data)
      console.log('steps', result.item.steps)
      console.log('result', result)
      console.log('path', `${NAMESPACE}/${ endpoint }/${funnelID}/step/`)

      yield setIsCreatingStep(false)
      yield {
        type: TYPES.CREATE_STEP,
        item: result.item,
      }
    }
    catch (e) {
      yield setCreatingStepError(e)

    }
  },

} )
