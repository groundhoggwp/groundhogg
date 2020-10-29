/**
 * External dependencies
 */
import {apiFetch} from '@wordpress/data-controls';



import { NAMESPACE } from '../constants'

// export default (endpoint) => ( {
//
//     endpoint,
//     * scheduleBroadcast (items) {
//         // yield setIsCreatingItems(true)
//         console.log(endpoint);
//         try {
//             const result = yield apiFetch({
//                 method: 'POST',
//                 path: `${ endpoint }`,
//                 data: items,
//             })
//
//             // yield setIsCreatingItems(false)
//             // yield {
//             //     type: TYPES.CREATE_ITEMS,
//             //     items: result.items,
//             // }
//
//             console.log(result);
//
//         }
//         catch (e) {
//             // yield setCreatingError(e)
//             console.log(e);
//         }
//     },
//
// } )



function isScheduling(){

}



/**
 * Schedules a new Broadcast.
 * Creates row in the broadcast table still needs to run bulk-jobs to enqueue events
 */
export function* scheduleBroadcast(data) {
    // yield setIsUpdating( true );
    // yield receiveBroadcasts( data );
    // yield setIsCreatingItems(true);
    try {
        const results = yield apiFetch({
            path: NAMESPACE + '/broadcasts/schedule/',
            method: 'POST',
            data,
        });
        // yield setIsCreatingItems(false);
        return {success: true, ...results};
    } catch (e) {
        console.log(e);
        yield setCreatingError(e)
    }
}

/**
 * Cancels the scheduled Broadcast
 */
export function* cancelBroadcast(data) {
    yield setIsUpdating(true);
    yield receiveBroadcasts(data);

    try {
        const results = yield apiFetch({
            path: NAMESPACE + '/broadcasts/cancel',
            method: 'POST',
            data,
        });

        yield setIsUpdating(false);
        return {success: true, ...results};
    } catch (error) {
        yield setUpdatingError(error);
        return {success: false, ...error};
    }
}