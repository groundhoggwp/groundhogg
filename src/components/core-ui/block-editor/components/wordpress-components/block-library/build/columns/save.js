"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = save;

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
function save(_ref) {
  var attributes = _ref.attributes;
  var verticalAlignment = attributes.verticalAlignment;
  var className = (0, _classnames2.default)((0, _defineProperty2.default)({}, "are-vertically-aligned-".concat(verticalAlignment), verticalAlignment));
  return (0, _element.createElement)("div", {
    className: className ? className : undefined
  }, (0, _element.createElement)(_blockEditor.InnerBlocks.Content, null));
}
//# sourceMappingURL=save.js.map