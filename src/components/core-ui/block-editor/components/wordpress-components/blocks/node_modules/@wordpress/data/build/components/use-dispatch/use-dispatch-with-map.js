"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _toConsumableArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toConsumableArray"));

var _lodash = require("lodash");

var _element = require("@wordpress/element");

var _useRegistry = _interopRequireDefault(require("../registry-provider/use-registry"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */

/**
 * Favor useLayoutEffect to ensure the store subscription callback always has
 * the dispatchMap from the latest render. If a store update happens between
 * render and the effect, this could cause missed/stale updates or
 * inconsistent state.
 *
 * Fallback to useEffect for server rendered components because currently React
 * throws a warning when using useLayoutEffect in that environment.
 */
var useIsomorphicLayoutEffect = typeof window !== 'undefined' ? _element.useLayoutEffect : _element.useEffect;
/**
 * Custom react hook for returning aggregate dispatch actions using the provided
 * dispatchMap.
 *
 * Currently this is an internal api only and is implemented by `withDispatch`
 *
 * @param {Function} dispatchMap  Receives the `registry.dispatch` function as
 *                                the first argument and the `registry` object
 *                                as the second argument.  Should return an
 *                                object mapping props to functions.
 * @param {Array}    deps         An array of dependencies for the hook.
 * @return {Object}  An object mapping props to functions created by the passed
 *                   in dispatchMap.
 */

var useDispatchWithMap = function useDispatchWithMap(dispatchMap, deps) {
  var registry = (0, _useRegistry.default)();
  var currentDispatchMap = (0, _element.useRef)(dispatchMap);
  useIsomorphicLayoutEffect(function () {
    currentDispatchMap.current = dispatchMap;
  });
  return (0, _element.useMemo)(function () {
    var currentDispatchProps = currentDispatchMap.current(registry.dispatch, registry);
    return (0, _lodash.mapValues)(currentDispatchProps, function (dispatcher, propName) {
      if (typeof dispatcher !== 'function') {
        // eslint-disable-next-line no-console
        console.warn("Property ".concat(propName, " returned from dispatchMap in useDispatchWithMap must be a function."));
      }

      return function () {
        var _currentDispatchMap$c;

        return (_currentDispatchMap$c = currentDispatchMap.current(registry.dispatch, registry))[propName].apply(_currentDispatchMap$c, arguments);
      };
    });
  }, [registry].concat((0, _toConsumableArray2.default)(deps)));
};

var _default = useDispatchWithMap;
exports.default = _default;
//# sourceMappingURL=use-dispatch-with-map.js.map