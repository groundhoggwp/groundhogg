"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.isURL = isURL;

var _reactNativeUrlPolyfill = require("react-native-url-polyfill");

/**
 * External dependencies
 */

/* eslint-disable jsdoc/valid-types */

/**
 * @type {typeof import('./is-url').isURL}
 */

/* eslint-enable */
function isURL(url) {
  // A URL can be considered value if the `URL` constructor is able to parse
  // it. The constructor throws an error for an invalid URL.
  try {
    new _reactNativeUrlPolyfill.URL(url);
    return true;
  } catch (error) {
    return false;
  }
}
//# sourceMappingURL=is-url.native.js.map