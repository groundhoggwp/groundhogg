"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.isAppleOS = isAppleOS;

var _lodash = require("lodash");

/**
 * External dependencies
 */

/**
 * Return true if platform is MacOS.
 *
 * @param {Object} _window   window object by default; used for DI testing.
 *
 * @return {boolean}         True if MacOS; false otherwise.
 */
function isAppleOS() {
  var _window = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : window;

  var platform = _window.navigator.platform;
  return platform.indexOf('Mac') !== -1 || (0, _lodash.includes)(['iPad', 'iPhone'], platform);
}
//# sourceMappingURL=platform.js.map