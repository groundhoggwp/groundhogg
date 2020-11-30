"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
Object.defineProperty(exports, "ifViewportMatches", {
  enumerable: true,
  get: function get() {
    return _ifViewportMatches.default;
  }
});
Object.defineProperty(exports, "withViewportMatch", {
  enumerable: true,
  get: function get() {
    return _withViewportMatch.default;
  }
});

require("./store");

var _listener = _interopRequireDefault(require("./listener"));

var _ifViewportMatches = _interopRequireDefault(require("./if-viewport-matches"));

var _withViewportMatch = _interopRequireDefault(require("./with-viewport-match"));

/**
 * Internal dependencies
 */

/**
 * Hash of breakpoint names with pixel width at which it becomes effective.
 *
 * @see _breakpoints.scss
 *
 * @type {Object}
 */
var BREAKPOINTS = {
  huge: 1440,
  wide: 1280,
  large: 960,
  medium: 782,
  small: 600,
  mobile: 480
};
/**
 * Hash of query operators with corresponding condition for media query.
 *
 * @type {Object}
 */

var OPERATORS = {
  '<': 'max-width',
  '>=': 'min-width'
};
(0, _listener.default)(BREAKPOINTS, OPERATORS);
//# sourceMappingURL=index.js.map