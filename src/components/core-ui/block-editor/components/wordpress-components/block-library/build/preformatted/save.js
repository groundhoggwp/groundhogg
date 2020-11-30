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
  var content = attributes.content;
  return (0, _element.createElement)(_blockEditor.RichText.Content, {
    tagName: "pre",
    value: content
  });
}
//# sourceMappingURL=save.js.map