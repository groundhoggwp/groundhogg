"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = save;

var _element = require("@wordpress/element");

var _blockEditor = require("@wordpress/block-editor");

var _utils = require("./utils");

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function save(_ref) {
  var attributes = _ref.attributes;
  return (0, _element.createElement)("pre", null, (0, _element.createElement)(_blockEditor.RichText.Content, {
    tagName: "code",
    value: (0, _utils.escape)(attributes.content)
  }));
}
//# sourceMappingURL=save.js.map