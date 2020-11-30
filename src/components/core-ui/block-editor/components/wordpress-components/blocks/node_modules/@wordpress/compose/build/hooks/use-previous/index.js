"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = usePrevious;

var _element = require("@wordpress/element");

/**
 * WordPress dependencies
 */

/**
 * Use something's value from the previous render.
 * Based on https://usehooks.com/usePrevious/.
 *
 * @template T
 *
 * @param {T} value The value to track.
 *
 * @return {T|undefined} The value from the previous render.
 */
function usePrevious(value) {
  // Disable reason: without an explicit type detail, the type of ref will be
  // inferred based on the initial useRef argument, which is undefined.
  // https://github.com/WordPress/gutenberg/pull/22597#issuecomment-633588366

  /* eslint-disable jsdoc/no-undefined-types */
  var ref = (0, _element.useRef)(
  /** @type {T|undefined} */
  undefined);
  /* eslint-enable jsdoc/no-undefined-types */
  // Store current value in ref.

  (0, _element.useEffect)(function () {
    ref.current = value;
  }, [value]); // Re-run when value changes.
  // Return previous value (happens before update in useEffect above).

  return ref.current;
}
//# sourceMappingURL=index.js.map