"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = save;

var _element = require("@wordpress/element");

var _blockEditor = require("@wordpress/block-editor");

/**
 * WordPress dependencies
 */
function save(_ref) {
  var attributes = _ref.attributes;
  var ordered = attributes.ordered,
      values = attributes.values,
      type = attributes.type,
      reversed = attributes.reversed,
      start = attributes.start;
  var tagName = ordered ? 'ol' : 'ul';
  return (0, _element.createElement)(_blockEditor.RichText.Content, {
    tagName: tagName,
    value: values,
    type: type,
    reversed: reversed,
    start: start,
    multiline: "li"
  });
}
//# sourceMappingURL=save.js.map