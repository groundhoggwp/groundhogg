"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _blockFormatControls = _interopRequireDefault(require("../block-format-controls"));

var _formatToolbar = _interopRequireDefault(require("./format-toolbar"));

/**
 * Internal dependencies
 */
var FormatToolbarContainer = function FormatToolbarContainer() {
  // Render regular toolbar
  return (0, _element.createElement)(_blockFormatControls.default, null, (0, _element.createElement)(_formatToolbar.default, null));
};

var _default = FormatToolbarContainer;
exports.default = _default;
//# sourceMappingURL=format-toolbar-container.native.js.map