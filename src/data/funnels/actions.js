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
  * createStep (stepData, funnelId) {
    yield setIsCreatingStep(false)

    try {
      const result = yield apiFetch({
        method: 'POST',
        path: `${NAMESPACE}/${ endpoint }/${funnelId}/step/`,
        data: {data:stepData}
      })

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

  * deleteStep (stepId, funnelId){
    yield setIsCreatingStep(false)

    try {
      const result = yield apiFetch({
        method: 'DELETE',
        path: `${NAMESPACE}/${ endpoint }/${funnelId}/step/${stepId}`
      })

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
  * updateStep (stepData, funnelId){
    yield setIsCreatingStep(false)

    try {
      const result = yield apiFetch({
        method: 'PATCH',
        path: `${NAMESPACE}/${ endpoint }/${funnelId}/step/${stepId}`,
        // data: {data:stepData}
      })

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
