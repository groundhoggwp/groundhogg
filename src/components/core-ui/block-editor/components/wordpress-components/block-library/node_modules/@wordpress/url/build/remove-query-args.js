"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.removeQueryArgs = removeQueryArgs;

var _qs = require("qs");

/**
 * External dependencies
 */

/**
 * Removes arguments from the query string of the url
 *
 * @param {string}    url  URL.
 * @param {...string} args Query Args.
 *
 * @example
 * ```js
 * const newUrl = removeQueryArgs( 'https://wordpress.org?foo=bar&bar=baz&baz=foobar', 'foo', 'bar' ); // https://wordpress.org?baz=foobar
 * ```
 *
 * @return {string} Updated URL.
 */
function removeQueryArgs(url) {
  var queryStringIndex = url.indexOf('?');
  var query = queryStringIndex !== -1 ? (0, _qs.parse)(url.substr(queryStringIndex + 1)) : {};
  var baseUrl = queryStringIndex !== -1 ? url.substr(0, queryStringIndex) : url;

  for (var _len = arguments.length, args = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
    args[_key - 1] = arguments[_key];
  }

  args.forEach(function (arg) {
    return delete query[arg];
  });
  return baseUrl + '?' + (0, _qs.stringify)(query);
}
//# sourceMappingURL=remove-query-args.js.map