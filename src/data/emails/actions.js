/**
 * External dependencies
 */
import {apiFetch} from '@wordpress/data-controls';
import TYPES from "./action-types";
import { NAMESPACE } from '../constants';
//
// // Creating
// function  setIsScheduling(isScheduling) {
//     return {
//         type: TYPES.SET_IS_SCHEDULING,
//         isScheduling,
//     }
// }
//
//
// // Creating
// function  setSchedulingError(error) {
//     return {
//         type: TYPES.SET_SCHEDULING_ERROR,
//         error
//     }
// }

export default (endpoint) => ( {
    endpoint,
    * SendEmailRaw (data) {
        // yield setIsScheduling(true);
        try {
            const result = yield apiFetch({
                method: 'POST',
                path: `${NAMESPACE}/${ endpoint }/send`,
                data,
            })
            // yield setIsScheduling(false);
            return {success: true, ...result};
        }
        catch (e) {
            // yield setSchedulingError(e);
            return {success: false, e };
        }
    },


    * SendEmailById ( emailId , data) {
        // yield setIsScheduling(true);
        try {
            const result = yield apiFetch({
                method: 'POST',
                path: `${NAMESPACE}/${ endpoint }/${emailId}/send`,
                data,
            })
            // yield setIsScheduling(false);
            return {success: true, ...result};
        }
        catch (e) {
            // yield setSchedulingError(e);
            return {success: false, e };
        }
    },

} )


