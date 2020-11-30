"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = BlockIcon;

var _element = require("@wordpress/element");

var _lodash = require("lodash");

var _reactNative = require("react-native");

var _components = require("@wordpress/components");

var _icons = require("@wordpress/icons");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
function BlockIcon(_ref) {
  var icon = _ref.icon,
      _ref$showColors = _ref.showColors,
      showColors = _ref$showColors === void 0 ? false : _ref$showColors;

  if ((0, _lodash.get)(icon, ['src']) === 'block-default') {
    icon = {
      src: _icons.blockDefault
    };
  }

  var renderedIcon = (0, _element.createElement)(_components.Icon, {
    icon: icon && icon.src ? icon.src : icon
  });
  var style = showColors ? {
    backgroundColor: icon && icon.background,
    color: icon && icon.foreground
  } : {};
  return (0, _element.createElement)(_reactNative.View, {
    style: style
  }, renderedIcon);
}
//# sourceMappingURL=index.native.js.map