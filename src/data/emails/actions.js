/**
 * External dependencies
 */
import {apiFetch} from '@wordpress/data-controls';

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
    * sendEmailRaw (data) {
      console.log('raw eamils?')
        // yield setIsScheduling(true);
        try {
          console.log('try and fetch')
            const result = yield apiFetch({
                method: 'POST',
                path: `${NAMESPACE}/${ endpoint }/send`,
                data,
            })
            console.log('send raw email result', result)
            // yield setIsScheduling(false);
            return {success: true, ...result};
        }
        catch (e) {
            // yield setSchedulingError(e);
            console.log('raw email error', e)
            return {success: false, e };
        }
    },


    * sendEmailById ( emailId , data) {
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
