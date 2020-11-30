"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.getQueryString = getQueryString;

/**
 * Returns the query string part of the URL.
 *
 * @param {string} url The full URL.
 *
 * @example
 * ```js
 * const queryString = getQueryString( 'http://localhost:8080/this/is/a/test?query=true#fragment' ); // 'query=true'
 * ```
 *
 * @return {string|void} The query string part of the URL.
 */
function getQueryString(url) {
  var query;

  try {
    query = new URL(url).search.substring(1);
  } catch (error) {}

  if (query) {
    return query;
  }
}
//# sourceMappingURL=get-query-string.js.map