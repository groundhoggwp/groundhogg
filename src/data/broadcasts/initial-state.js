import { QUERY_DEFAULTS } from '../constants';

export const initialState = {
	isRequesting: false,
	isUpdating: false,
	isAdding: false,
	isDeleting: false,
	total: 0,
	context: {},
	data: [],
	error: {},
};
