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
    action,
    context,
    numItemsPerRequest,
    onFinish,
    onError
  } = getState().bulkJob

  let itemsOffset = 0;

  const sendRequest = () => {

    axios.post(rest_base + '/bulkjob/' + action, {
      items_per_request: numItemsPerRequest,
      items_offset: itemsOffset,
      context: context,
    }).then(response => {

      itemsOffset += numItemsPerRequest;

      dispatch({
        type: BULK_JOB_PROCESSED_ITEMS,
      })

      if ( ! response.data.finished ){
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

