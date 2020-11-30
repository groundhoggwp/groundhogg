"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _components = require("@wordpress/components");

var _blockFormatControls = _interopRequireDefault(require("../block-format-controls"));

var _formatToolbar = _interopRequireDefault(require("./format-toolbar"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var FormatToolbarContainer = function FormatToolbarContainer(_ref) {
  var inline = _ref.inline,
      anchorRef = _ref.anchorRef;

  if (inline) {
    // Render in popover
    return (0, _element.createElement)(_components.Popover, {
      noArrow: true,
      position: "top center",
      focusOnMount: false,
      anchorRef: anchorRef,
      className: "block-editor-rich-text__inline-format-toolbar"
    }, (0, _element.createElement)(_formatToolbar.default, null));
  } // Render regular toolbar


  return (0, _element.createElement)(_blockFormatControls.default, null, (0, _element.createElement)(_formatToolbar.default, null));
};

var _default = FormatToolbarContainer;
exports.default = _default;
//# sourceMappingURL=format-toolbar-container.js.map