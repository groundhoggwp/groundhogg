/**
 * External dependencies
 */
import isPromise from 'is-promise';
/**
 * Simplest possible promise redux middleware.
 *
 * @return {Function} middleware.
 */

var promiseMiddleware = function promiseMiddleware() {
  return function (next) {
    return function (action) {
      if (isPromise(action)) {
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

export default promiseMiddleware;
//# sourceMappingURL=promise-middleware.js.map