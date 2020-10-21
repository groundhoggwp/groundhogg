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


// Creating
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

// Update
function setIsUpdatingStep (isUpdating) {
  return {
    type: TYPES.SET_IS_UPDATING,
    isUpdating,
  }
}

function setIsUpdatingStepError (error) {
  return {
    type: TYPES.SET_UPDATING_ERROR,
    error,
  }
}

// Delete
function setIsDeletingStep (isDeleting) {
  return {
    type: TYPES.SET_IS_DELETING,
    isDeleting,
  }
}

function setIsDeletingStepError (error) {
  return {
    type: TYPES.SET_DELETING_ERROR,
    error,
  }
}


export default (endpoint) => ( {
  endpoint,
  * createStep (stepData, funnelId) {
    yield setIsCreatingStep(true)

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
    yield setIsDeletingStep(true)

    try {
      const result = yield apiFetch({
        method: 'DELETE',
        path: `${NAMESPACE}/${ endpoint }/${funnelId}/step/${stepId}`
      })

      yield setIsDeletingStep(false)
      yield {
        type: TYPES.DELETE_STEP,
        item: result.item,
      }
    }
    catch (e) {
      yield setIsDeletingStepError(e)
    }
  },
  * updateStep (stepData, funnelId){
    yield setIsUpdatingStep(true)

    try {
      const result = yield apiFetch({
        method: 'PATCH',
        path: `${NAMESPACE}/${ endpoint }/${funnelId}/step/${stepId}`,
        data: {data:stepData}
      })

      yield setIsUpdatingStep(false)
      yield {
        type: TYPES.UPDATE_STEP,
        item: result.item,
      }
    }
    catch (e) {
      yield setIsUpdatingStepError(e)
    }
  },

} )
