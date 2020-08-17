import {
  BULK_JOB_ERROR, BULK_JOB_FINISHED,
  BULK_JOB_INIT,
  BULK_JOB_PROCESSED_ITEMS,
} from './types'
import axios from 'axios'

const {
  rest_base
} = groundhogg;

export const bulkJobInit = (init) => dispatch => {

  dispatch({
    type: BULK_JOB_INIT,
    payload: init,
  })
}

export const bulkJobProcessItems = () => (dispatch, getState) => {

  const {
    items,
    context,
    action,
    numItemsPerRequest,
    onFinish,
    onError
  } = getState().bulkJob

  // console.debug( getState().bulkJob )

  const sendRequest = () => {
    let itemsToComplete = items.splice(0, numItemsPerRequest)

    console.debug( items, itemsToComplete )

    axios.post(rest_base + '/bulkjob/' + action, {
      items: itemsToComplete,
      the_end: items.length === 0,
      context: context,
    }).then(response => {

      dispatch({
        type: BULK_JOB_PROCESSED_ITEMS,
        payload: {
          lastResponse: response.data,
          completed: itemsToComplete,
          remaining: items,
        },
      })

      if ( items.length > 0 ){
        sendRequest();
      } else {
        dispatch({
          type: BULK_JOB_FINISHED,
        })

        onFinish( response.data )
      }

    }).catch(error => {
      dispatch({
        type: BULK_JOB_ERROR,
        payload: error,
      })

      onError( error )
    })
  }

  sendRequest();

}

