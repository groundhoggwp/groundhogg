"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.addQueryArgs = addQueryArgs;

var _qs = require("qs");

/**
 * External dependencies
 */

/**
 * Appends arguments as querystring to the provided URL. If the URL already
 * includes query arguments, the arguments are merged with (and take precedent
 * over) the existing set.
 *
 * @param {string} [url='']  URL to which arguments should be appended. If omitted,
 *                           only the resulting querystring is returned.
 * @param {Object} [args]    Query arguments to apply to URL.
 *
 * @example
 * ```js
 * const newURL = addQueryArgs( 'https://google.com', { q: 'test' } ); // https://google.com/?q=test
 * ```
 *
 * @return {string} URL with arguments applied.
 */
function addQueryArgs() {
  var url = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
  var args = arguments.length > 1 ? arguments[1] : undefined;

  // If no arguments are to be appended, return original URL.
  if (!args || !Object.keys(args).length) {
    return url;
  }

  var baseUrl = url; // Determine whether URL already had query arguments.

  var queryStringIndex = url.indexOf('?');

  if (queryStringIndex !== -1) {
    // Merge into existing query arguments.
    args = Object.assign((0, _qs.parse)(url.substr(queryStringIndex + 1)), args); // Change working base URL to omit previous query arguments.

    baseUrl = baseUrl.substr(0, queryStringIndex);
  }

  return baseUrl + '?' + (0, _qs.stringify)(args);
}
//# sourceMappingURL=add-query-args.js.map