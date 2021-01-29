/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { NAMESPACE } from '../constants';
import TYPES from './action-types';


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
				type: TYPES.BULK_JOB_PROCESSED_ITEMS,
			})

			if ( ! response.data.finished ){
				sendRequest();
			} else {
				dispatch({
				type: TYPES.BULK_JOB_FINISHED,
				})

				onFinish( response.data )
			}

		}).catch(error => {
			dispatch({
				type: TYPES.BULK_JOB_ERROR,
				payload: error,
			})

			onError( error )
		})
	}

	sendRequest();

}