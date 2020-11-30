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
  var align = attributes.align,
      value = attributes.value,
      citation = attributes.citation;
  var className = (0, _classnames2.default)((0, _defineProperty2.default)({}, "has-text-align-".concat(align), align));
  return (0, _element.createElement)("blockquote", {
    className: className
  }, (0, _element.createElement)(_blockEditor.RichText.Content, {
    multiline: true,
    value: value
  }), !_blockEditor.RichText.isEmpty(citation) && (0, _element.createElement)(_blockEditor.RichText.Content, {
    tagName: "cite",
    value: citation
  }));
}
//# sourceMappingURL=save.js.map