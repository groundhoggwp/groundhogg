/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { QUERY_DEFAULTS } from '../constants';


export function addStep( itemId, step ) {
	return {
		type: TYPES.ADD_STEP,
		itemId,
		step
	};
}

// export function receieveStep( itemId ) {
// 	return {
// 		type: TYPES.FETCH_STEP,
// 		itemId
// 	};
// }
// export function receieveAllSteps( itemId, steps ) {
// 	return {
// 		type: TYPES.FETCH_ALL_STEPS,
// 		itemId,
// 		steps
// 	};
// }
//
// export function updateSteps( itemId, step ) {
// 	return {
// 		type: TYPES.UPDATE_STEP,
// 		itemId
// 	};
// }
// export function deleteSteps( itemId, step ) {
// 	return {
// 		type: TYPES.DELETE_STEP,
// 		itemId,
// 		step
// 	};
// }
