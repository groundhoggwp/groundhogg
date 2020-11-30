"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = DescenderLines;

var _element = require("@wordpress/element");

var _lodash = require("lodash");

var _classnames = _interopRequireDefault(require("classnames"));

/**
 * External dependencies
 */
var lineClassName = 'block-editor-block-navigator-descender-line';

function DescenderLines(_ref) {
  var level = _ref.level,
      isLastRow = _ref.isLastRow,
      terminatedLevels = _ref.terminatedLevels;
  return (0, _lodash.times)(level - 1, function (index) {
    // The first 'level' that has a descender line is level 2.
    // Add 2 to the zero-based index below to reflect that.
    var currentLevel = index + 2;
    var hasItem = currentLevel === level;
    return (0, _element.createElement)("div", {
      key: index,
      "aria-hidden": "true",
      className: (0, _classnames.default)(lineClassName, {
        'has-item': hasItem,
        'is-last-row': isLastRow,
        'is-terminated': terminatedLevels.includes(currentLevel)
      })
    });
  });
}
//# sourceMappingURL=descender-lines.js.map