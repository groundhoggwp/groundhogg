/**
 * External dependencies
 */
import { create } from 'rungen';
import { map } from 'lodash';
import isPromise from 'is-promise';
/**
 * Internal dependencies
 */

import { isActionOfType, isAction } from './is-action';
/**
 * Create a co-routine runtime.
 *
 * @param {Object}    controls Object of control handlers.
 * @param {Function}  dispatch Unhandled action dispatch.
 *
 * @return {Function} co-routine runtime
 */

export default function createRuntime() {
  var controls = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
  var dispatch = arguments.length > 1 ? arguments[1] : undefined;
  var rungenControls = map(controls, function (control, actionType) {
    return function (value, next, iterate, yieldNext, yieldError) {
      if (!isActionOfType(value, actionType)) {
        return false;
      }

      var routine = control(value);

      if (isPromise(routine)) {
        // Async control routine awaits resolution.
        routine.then(yieldNext, yieldError);
      } else {
        yieldNext(routine);
      }

      return true;
    };
  });

  var unhandledActionControl = function unhandledActionControl(value, next) {
    if (!isAction(value)) {
      return false;
    }

    dispatch(value);
    next();
    return true;
  };

  rungenControls.push(unhandledActionControl);
  var rungenRuntime = create(rungenControls);
  return function (action) {
    return new Promise(function (resolve, reject) {
      return rungenRuntime(action, function (result) {
        if (isAction(result)) {
          dispatch(result);
        }

        resolve(result);
      }, reject);
    });
  };
}
//# sourceMappingURL=runtime.js.map