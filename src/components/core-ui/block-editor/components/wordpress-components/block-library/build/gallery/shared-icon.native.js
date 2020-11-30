"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.sharedIcon = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _components = require("@wordpress/components");

var _compose = require("@wordpress/compose");

var _icons = require("@wordpress/icons");

var _styles = _interopRequireDefault(require("./styles.scss"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var IconWithColorScheme = (0, _compose.withPreferredColorScheme)(function (_ref) {
  var getStylesFromColorScheme = _ref.getStylesFromColorScheme;
  var colorSchemeStyles = getStylesFromColorScheme(_styles.default.icon, _styles.default.iconDark);
  return (0, _element.createElement)(_components.Icon, (0, _extends2.default)({
    icon: _icons.gallery
  }, colorSchemeStyles));
});
var sharedIcon = (0, _element.createElement)(IconWithColorScheme, null);
exports.sharedIcon = sharedIcon;
//# sourceMappingURL=shared-icon.native.js.map