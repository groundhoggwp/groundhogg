"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.space = space;
var SPACE_GRID_BASE = 8;
/**
 * Creates a spacing CSS value (px) based on grid system values.
 *
 * @param {number} value Multiplier against the grid base value (8)
 * @return {string} The spacing value (px).
 */

function space() {
  var value = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 1;
  if (isNaN(value)) return "".concat(SPACE_GRID_BASE, "px");
  return "".concat(SPACE_GRID_BASE * value, "px");
}
//# sourceMappingURL=space.js.map