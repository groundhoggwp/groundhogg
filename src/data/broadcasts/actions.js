/**
 * External dependencies
 */
import {apiFetch} from '@wordpress/data-controls';
import TYPES from "./action-types";
import { NAMESPACE } from '../constants';

// Creating
function  setIsScheduling(isScheduling) {
    return {
        type: TYPES.SET_IS_SCHEDULING,
        isScheduling,
    }
}


// Creating
function  setSchedulingError(error) {
    return {
        type: TYPES.SET_SCHEDULING_ERROR,
        error
    }
}


export default (endpoint) => ( {
    endpoint,
    * scheduleBroadcast (data) {
        yield setIsScheduling(true);
        try {
            const result = yield apiFetch({
                method: 'POST',
                path: `${NAMESPACE}/${ endpoint }/schedule`,
                data,
            })
            yield setIsScheduling(false);
            return {success: true, ...result};
        }
        catch (e) {
            yield setSchedulingError(e);
            return {success: false, e };
        }
    },
} )




//
// /**
//  * Schedules a new Broadcast.
//  * Creates row in the broadcast table still needs to run bulk-jobs to enqueue events
//  */
// export function* scheduleBroadcast(data) {
//     // yield setIsUpdating( true );
//     // yield receiveBroadcasts( data );
//     // yield setIsCreatingItems(true);
//     try {
//         const results = yield apiFetch({
//             path: NAMESPACE + '/broadcasts/schedule/',
//             method: 'POST',
//             data,
//         });
//         // yield setIsCreatingItems(false);
//         return {success: true, ...results};
//     } catch (e) {
//         console.log(e);
//         yield setCreatingError(e)
//     }
// }
//
// /**
//  * Cancels the scheduled Broadcast
//  */
// export function* cancelBroadcast(data) {
//     yield setIsUpdating(true);
//     yield receiveBroadcasts(data);
//
//     try {
//         const results = yield apiFetch({
//             path: NAMESPACE + '/broadcasts/cancel',
//             method: 'POST',
//             data,
//         });
//
//         yield setIsUpdating(false);
//         return {success: true, ...results};
//     } catch (error) {
//         yield setUpdatingError(error);
//         return {success: false, ...error};
//     }
// }