"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = BlockNavigationEditor;

var _element = require("@wordpress/element");

var _i18n = require("@wordpress/i18n");

var _ = require("../");

var _blockSlot = require("./block-slot");

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function BlockNavigationEditor(_ref) {
  var value = _ref.value,
      onChange = _ref.onChange;
  return (0, _element.createElement)(_blockSlot.BlockNavigationBlockFill, null, (0, _element.createElement)(_.RichText, {
    value: value,
    onChange: onChange,
    placeholder: (0, _i18n.__)('Navigation item'),
    keepPlaceholderOnFocus: true,
    withoutInteractiveFormatting: true,
    allowedFormats: ['core/bold', 'core/italic', 'core/image', 'core/strikethrough']
  }));
}
//# sourceMappingURL=editor.js.map