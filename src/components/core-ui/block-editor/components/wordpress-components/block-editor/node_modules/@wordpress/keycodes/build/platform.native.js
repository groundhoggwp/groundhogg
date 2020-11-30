"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.isAppleOS = isAppleOS;

var _reactNative = require("react-native");

/**
 * External dependencies
 */

/**
 * Return true if platform is iOS.
 *
 * @return {boolean}         True if iOS; false otherwise.
 */
function isAppleOS() {
  return _reactNative.Platform.OS === 'ios';
}
//# sourceMappingURL=platform.native.js.map