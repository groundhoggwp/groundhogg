"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = useAsyncMode;

var _element = require("@wordpress/element");

var _context = require("./context");

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function useAsyncMode() {
  return (0, _element.useContext)(_context.Context);
}
//# sourceMappingURL=use-async-mode.js.map