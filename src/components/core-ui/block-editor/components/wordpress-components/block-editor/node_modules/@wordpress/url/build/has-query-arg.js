"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.hasQueryArg = hasQueryArg;

var _getQueryArg = require("./get-query-arg");

/**
 * Internal dependencies
 */

/**
 * Determines whether the URL contains a given query arg.
 *
 * @param {string} url URL.
 * @param {string} arg Query arg name.
 *
 * @example
 * ```js
 * const hasBar = hasQueryArg( 'https://wordpress.org?foo=bar&bar=baz', 'bar' ); // true
 * ```
 *
 * @return {boolean} Whether or not the URL contains the query arg.
 */
function hasQueryArg(url, arg) {
  return (0, _getQueryArg.getQueryArg)(url, arg) !== undefined;
}
//# sourceMappingURL=has-query-arg.js.map