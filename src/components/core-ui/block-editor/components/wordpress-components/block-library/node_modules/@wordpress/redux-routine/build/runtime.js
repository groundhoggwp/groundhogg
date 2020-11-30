"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = createRuntime;

var _rungen = require("rungen");

var _lodash = require("lodash");

var _isPromise = _interopRequireDefault(require("is-promise"));

var _isAction = require("./is-action");

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

/**
 * Create a co-routine runtime.
 *
 * @param {Object}    controls Object of control handlers.
 * @param {Function}  dispatch Unhandled action dispatch.
 *
 * @return {Function} co-routine runtime
 */
function createRuntime() {
  var controls = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
  var dispatch = arguments.length > 1 ? arguments[1] : undefined;
  var rungenControls = (0, _lodash.map)(controls, function (control, actionType) {
    return function (value, next, iterate, yieldNext, yieldError) {
      if (!(0, _isAction.isActionOfType)(value, actionType)) {
        return false;
      }

      var routine = control(value);

      if ((0, _isPromise.default)(routine)) {
        // Async control routine awaits resolution.
        routine.then(yieldNext, yieldError);
      } else {
        yieldNext(routine);
      }

      return true;
    };
  });

  var unhandledActionControl = function unhandledActionControl(value, next) {
    if (!(0, _isAction.isAction)(value)) {
      return false;
    }

    dispatch(value);
    next();
    return true;
  };

  rungenControls.push(unhandledActionControl);
  var rungenRuntime = (0, _rungen.create)(rungenControls);
  return function (action) {
    return new Promise(function (resolve, reject) {
      return rungenRuntime(action, function (result) {
        if ((0, _isAction.isAction)(result)) {
          dispatch(result);
        }

        resolve(result);
      }, reject);
    });
  };
}
//# sourceMappingURL=runtime.js.map