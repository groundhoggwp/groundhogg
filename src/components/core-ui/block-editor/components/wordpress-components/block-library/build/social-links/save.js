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
  var className = _ref.className;
  return (0, _element.createElement)("ul", {
    className: className
  }, (0, _element.createElement)(_blockEditor.InnerBlocks.Content, null));
}
//# sourceMappingURL=save.js.map