/**
 * External dependencies
 */
import { controls as dataControls } from '@wordpress/data-controls';

import apiFetch from '@wordpress/api-fetch';

export const fetchWithHeaders = ( options ) => {
	return {
		type: 'FETCH_WITH_HEADERS',
		options,
	};
};

const controls = {
	...dataControls,
	async FETCH_WITH_HEADERS( { options } ) {
		const response = await apiFetch({ ...options, parse: false });
		const [headers, status, data] = await Promise.all([
			response.headers,
			response.status,
			response.json(),
		]);
		return ({
			headers,
			status,
			data,
		});
	},
};

export default controls;
