"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = _default;

var _compress = _interopRequireDefault(require("./compress"));

var _identity = _interopRequireDefault(require("./identity"));

// Adapted from https://github.com/reworkcss/css
// because we needed to remove source map support.

/**
 * Internal dependencies
 */

/**
 * Stringfy the given AST `node`.
 *
 * Options:
 *
 *  - `compress` space-optimized output
 *  - `sourcemap` return an object with `.code` and `.map`
 *
 * @param {Object} node
 * @param {Object} [options]
 * @return {string}
 */
function _default(node, options) {
  options = options || {};
  var compiler = options.compress ? new _compress.default(options) : new _identity.default(options);
  var code = compiler.compile(node);
  return code;
}
//# sourceMappingURL=index.js.map