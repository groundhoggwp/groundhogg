/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { NAMESPACE } from '../constants';

export const fetchEmails = () => (dispatch, getState) => {
const {
	query,
} = getState().contactList

dispatch(fetchContactsRequest())

axios.get(groundhogg.rest_base + '/contacts', {
	params: {
	query: query,
	},
}).then(response => {
	dispatch(fetchContactsSuccess(response.data))
}).catch(error => fetchContactsFailed(error))
}
