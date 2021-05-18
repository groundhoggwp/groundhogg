/**
 * Internal dependencies
 */
import TYPES from './action-types'
import { UPDATE_ITEM } from '../base-object/action-types'
import { NAMESPACE } from '../constants';

/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls'
import { setIsUpdating, setUpdatingError } from '../events/actions'

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

  /**
   * Update the edges
   *
   * Assume a well formed array of edges
   *
   * @param funnelId
   * @param edges
   * @returns {Generator<{funnelId, edges, type: string}, void, *>}
   */
  * updateEdges ( funnelId, edges ){
    yield {
      type: TYPES.UPDATE_EDGES,
      funnelId,
      edges
    }
  },

  /**
   * Create a step
   *
   * Assume we have a well formed step object
   *
   * @param funnelId
   * @param step
   * @returns {Generator<{request: Object, type: string}|{item, type: string}|{type: *, error}|{isCreating, type: *}, void, *>}
   */
  * createStep (funnelId, step) {
    yield {
      type: TYPES.CREATE_STEP,
      funnelId,
      step
    }
  },

  /**
   * Assume we have a well formed step object
   *
   * @param funnelId
   * @param step
   * @returns {Generator<{funnelId, step, type: string}, void, *>}
   */
  * deleteStep (funnelId, step){
    yield {
      type: TYPES.DELETE_STEP,
      funnelId,
      step
    }
  },

  /**
   * Assume we have a well formed step object
   *
   * @param funnelId
   * @param step
   * @returns {Generator<{funnelId, step, type: string}, void, *>}
   */
  * updateStep (funnelId, step){
    yield {
      type: TYPES.UPDATE_STEP,
      funnelId,
      step
    }
  },


  /**
   * Auto save the funnel
   *
   * Assume we have a well formed funnel object
   *
   * @param funnel
   * @returns {Generator<{request: Object, type: string}|{item, type: string}|{type: *, error}|{isCreating, type: *}, void, *>}
   */
  * autoSave (funnel) {
    yield setIsUpdating(true)

    try {
      const result = yield apiFetch({
        method: 'POST',
        path: `${NAMESPACE}/${ endpoint }/${funnel.ID}`,
        ...funnel
      })

      yield setIsUpdating(false)
      yield {
        type: UPDATE_ITEM,
        item: result.item,
      }
    }
    catch (e) {
      yield setUpdatingError(e)
    }
  },


} )
