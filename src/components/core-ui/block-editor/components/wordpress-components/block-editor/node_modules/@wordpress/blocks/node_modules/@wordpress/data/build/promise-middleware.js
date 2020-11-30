"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _isPromise = _interopRequireDefault(require("is-promise"));

/**
 * External dependencies
 */

/**
 * Simplest possible promise redux middleware.
 *
 * @return {Function} middleware.
 */
var promiseMiddleware = function promiseMiddleware() {
  return function (next) {
    return function (action) {
      if ((0, _isPromise.default)(action)) {
        return action.then(function (resolvedAction) {
          if (resolvedAction) {
            return next(resolvedAction);
          }
        });
      }

      return next(action);
    };
  };
};

var _default = promiseMiddleware;
exports.default = _default;
//# sourceMappingURL=promise-middleware.js.map