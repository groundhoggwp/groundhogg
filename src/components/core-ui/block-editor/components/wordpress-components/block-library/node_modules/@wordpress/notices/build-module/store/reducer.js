import _toConsumableArray from "@babel/runtime/helpers/esm/toConsumableArray";

/**
 * External dependencies
 */
import { reject } from 'lodash';
/**
 * Internal dependencies
 */

import onSubKey from './utils/on-sub-key';
/**
 * Reducer returning the next notices state. The notices state is an object
 * where each key is a context, its value an array of notice objects.
 *
 * @param {Object} state  Current state.
 * @param {Object} action Dispatched action.
 *
 * @return {Object} Updated state.
 */

var notices = onSubKey('context')(function () {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : [];
  var action = arguments.length > 1 ? arguments[1] : undefined;

  switch (action.type) {
    case 'CREATE_NOTICE':
      // Avoid duplicates on ID.
      return [].concat(_toConsumableArray(reject(state, {
        id: action.notice.id
      })), [action.notice]);

    case 'REMOVE_NOTICE':
      return reject(state, {
        id: action.id
      });
  }

  return state;
});
export default notices;
//# sourceMappingURL=reducer.js.map