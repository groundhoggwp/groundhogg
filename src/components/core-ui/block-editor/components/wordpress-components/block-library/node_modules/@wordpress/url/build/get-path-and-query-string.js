"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.getPathAndQueryString = getPathAndQueryString;

var _ = require(".");

/**
 * Internal dependencies
 */

/**
 * Returns the path part and query string part of the URL.
 *
 * @param {string} url The full URL.
 *
 * @example
 * ```js
 * const pathAndQueryString1 = getPathAndQueryString( 'http://localhost:8080/this/is/a/test?query=true' ); // '/this/is/a/test?query=true'
 * const pathAndQueryString2 = getPathAndQueryString( 'https://wordpress.org/help/faq/' ); // '/help/faq'
 * ```
 *
 * @return {string} The path part and query string part of the URL.
 */
function getPathAndQueryString(url) {
  var path = (0, _.getPath)(url);
  var queryString = (0, _.getQueryString)(url);
  var value = '/';
  if (path) value += path;
  if (queryString) value += "?".concat(queryString);
  return value;
}
//# sourceMappingURL=get-path-and-query-string.js.map