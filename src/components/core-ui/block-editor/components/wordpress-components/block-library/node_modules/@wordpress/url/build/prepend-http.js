"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.prependHTTP = prependHTTP;

var _isEmail = require("./is-email");

/**
 * Internal dependencies
 */
var USABLE_HREF_REGEXP = /^(?:[a-z]+:|#|\?|\.|\/)/i;
/**
 * Prepends "http://" to a url, if it looks like something that is meant to be a TLD.
 *
 * @param {string} url The URL to test.
 *
 * @example
 * ```js
 * const actualURL = prependHTTP( 'wordpress.org' ); // http://wordpress.org
 * ```
 *
 * @return {string} The updated URL.
 */

function prependHTTP(url) {
  if (!url) {
    return url;
  }

  url = url.trim();

  if (!USABLE_HREF_REGEXP.test(url) && !(0, _isEmail.isEmail)(url)) {
    return 'http://' + url;
  }

  return url;
}
//# sourceMappingURL=prepend-http.js.map