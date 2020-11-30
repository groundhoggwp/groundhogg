"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = BlockConvertButton;

var _element = require("@wordpress/element");

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

/**
 * WordPress dependencies
 */
function BlockConvertButton(_ref) {
  var shouldRender = _ref.shouldRender,
      onClick = _ref.onClick,
      small = _ref.small;

  if (!shouldRender) {
    return null;
  }

  var label = (0, _i18n.__)('Convert to Blocks');
  return (0, _element.createElement)(_components.MenuItem, {
    onClick: onClick
  }, !small && label);
}
//# sourceMappingURL=block-convert-button.js.map