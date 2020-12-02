import { QUERY_DEFAULTS } from '../constants';

export const initialState = {
	isRequesting: false,
	isUpdating: false,
	isAdding: false,
	isDeleting: false,
	showFilters: false,
	total: 0,
	context: {},
	selected: [],
	query: QUERY_DEFAULTS,
	data: [],
	error: {},
	files : [],
	totalFiles : 0
};