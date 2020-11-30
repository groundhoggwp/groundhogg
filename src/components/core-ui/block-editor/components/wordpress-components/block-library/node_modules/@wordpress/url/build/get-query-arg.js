"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.getQueryArg = getQueryArg;

var _qs = require("qs");

/**
 * External dependencies
 */

/* eslint-disable jsdoc/valid-types */

/**
 * @typedef {{[key: string]: QueryArgParsed}} QueryArgObject
 */

/* eslint-enable */

/**
 * @typedef {string|string[]|QueryArgObject} QueryArgParsed
 */

/**
 * Returns a single query argument of the url
 *
 * @param {string} url URL.
 * @param {string} arg Query arg name.
 *
 * @example
 * ```js
 * const foo = getQueryArg( 'https://wordpress.org?foo=bar&bar=baz', 'foo' ); // bar
 * ```
 *
 * @return {QueryArgParsed|undefined} Query arg value.
 */
function getQueryArg(url, arg) {
  var queryStringIndex = url.indexOf('?');
  var query = queryStringIndex !== -1 ? (0, _qs.parse)(url.substr(queryStringIndex + 1)) : {};
  return query[arg];
}
//# sourceMappingURL=get-query-arg.js.map