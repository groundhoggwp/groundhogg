"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _usePrevious = _interopRequireDefault(require("../use-previous"));

/**
 * Internal dependencies
 */

/**
 * Hook that performs a shallow comparison between the preview value of an object
 * and the new one, if there's a difference, it prints it to the console.
 * this is useful in performance related work, to check why a component re-renders.
 *
 *  @example
 *
 * ```jsx
 * function MyComponent(props) {
 *    useWarnOnChange(props);
 *
 *    return "Something";
 * }
 * ```
 *
 * @param {Object} object Object which changes to compare.
 * @param {string} prefix Just a prefix to show when console logging.
 */
function useWarnOnChange(object) {
  var prefix = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'Change detection';
  var previousValues = (0, _usePrevious.default)(object);
  Object.entries(previousValues !== null && previousValues !== void 0 ? previousValues : []).forEach(function (_ref) {
    var _ref2 = (0, _slicedToArray2.default)(_ref, 2),
        key = _ref2[0],
        value = _ref2[1];

    if (value !== object[key]) {
      // eslint-disable-next-line no-console
      console.warn("".concat(prefix, ": ").concat(key, " key changed:"), value, object[key]);
    }
  });
}

var _default = useWarnOnChange;
exports.default = _default;
//# sourceMappingURL=index.js.map