"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = separatorSave;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _classnames2 = _interopRequireDefault(require("classnames"));

var _blockEditor = require("@wordpress/block-editor");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
function separatorSave(_ref) {
  var _classnames;

  var attributes = _ref.attributes;
  var color = attributes.color,
      customColor = attributes.customColor; // the hr support changing color using border-color, since border-color
  // is not yet supported in the color palette, we use background-color

  var backgroundClass = (0, _blockEditor.getColorClassName)('background-color', color); // the dots styles uses text for the dots, to change those dots color is
  // using color, not backgroundColor

  var colorClass = (0, _blockEditor.getColorClassName)('color', color);
  var separatorClasses = (0, _classnames2.default)((_classnames = {
    'has-text-color has-background': color || customColor
  }, (0, _defineProperty2.default)(_classnames, backgroundClass, backgroundClass), (0, _defineProperty2.default)(_classnames, colorClass, colorClass), _classnames));
  var separatorStyle = {
    backgroundColor: backgroundClass ? undefined : customColor,
    color: colorClass ? undefined : customColor
  };
  return (0, _element.createElement)("hr", {
    className: separatorClasses,
    style: separatorStyle
  });
}
//# sourceMappingURL=save.js.map