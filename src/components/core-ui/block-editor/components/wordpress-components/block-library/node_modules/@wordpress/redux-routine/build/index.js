"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = createMiddleware;

var _isGenerator = _interopRequireDefault(require("./is-generator"));

var _runtime = _interopRequireDefault(require("./runtime"));

/**
 * Internal dependencies
 */

/**
 * Creates a Redux middleware, given an object of controls where each key is an
 * action type for which to act upon, the value a function which returns either
 * a promise which is to resolve when evaluation of the action should continue,
 * or a value. The value or resolved promise value is assigned on the return
 * value of the yield assignment. If the control handler returns undefined, the
 * execution is not continued.
 *
 * @param {Object} controls Object of control handlers.
 *
 * @return {Function} Co-routine runtime
 */
function createMiddleware() {
  var controls = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
  return function (store) {
    var runtime = (0, _runtime.default)(controls, store.dispatch);
    return function (next) {
      return function (action) {
        if (!(0, _isGenerator.default)(action)) {
          return next(action);
        }

        return runtime(action);
      };
    };
  };
}
//# sourceMappingURL=index.js.map