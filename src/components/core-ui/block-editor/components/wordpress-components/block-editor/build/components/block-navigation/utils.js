"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.getBlockPositionDescription = void 0;

var _i18n = require("@wordpress/i18n");

/**
 * WordPress dependencies
 */
var getBlockPositionDescription = function getBlockPositionDescription(position, siblingCount, level) {
  return (0, _i18n.sprintf)(
  /* translators: 1: The numerical position of the block. 2: The total number of blocks. 3. The level of nesting for the block. */
  (0, _i18n.__)('Block %1$d of %2$d, Level %3$d'), position, siblingCount, level);
};

exports.getBlockPositionDescription = getBlockPositionDescription;
//# sourceMappingURL=utils.js.map