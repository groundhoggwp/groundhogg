"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _reactNative = require("react-native");

var _components = require("@wordpress/components");

var _compose = require("@wordpress/compose");

var _blocks = require("@wordpress/blocks");

var _style = _interopRequireDefault(require("./style.scss"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function Warning(_ref) {
  var title = _ref.title,
      message = _ref.message,
      icon = _ref.icon,
      iconClass = _ref.iconClass,
      preferredColorScheme = _ref.preferredColorScheme,
      getStylesFromColorScheme = _ref.getStylesFromColorScheme,
      viewProps = (0, _objectWithoutProperties2.default)(_ref, ["title", "message", "icon", "iconClass", "preferredColorScheme", "getStylesFromColorScheme"]);
  icon = icon && (0, _blocks.normalizeIconObject)(icon);
  var internalIconClass = 'warning-icon' + '-' + preferredColorScheme;
  var titleStyle = getStylesFromColorScheme(_style.default.title, _style.default.titleDark);
  var messageStyle = getStylesFromColorScheme(_style.default.message, _style.default.messageDark);
  return (0, _element.createElement)(_reactNative.View, (0, _extends2.default)({
    style: getStylesFromColorScheme(_style.default.container, _style.default.containerDark)
  }, viewProps), icon && (0, _element.createElement)(_reactNative.View, {
    style: _style.default.icon
  }, (0, _element.createElement)(_components.Icon, {
    className: iconClass || internalIconClass,
    icon: icon && icon.src ? icon.src : icon
  })), title && (0, _element.createElement)(_reactNative.Text, {
    style: titleStyle
  }, title), message && (0, _element.createElement)(_reactNative.Text, {
    style: messageStyle
  }, message));
}

var _default = (0, _compose.withPreferredColorScheme)(Warning);

exports.default = _default;
//# sourceMappingURL=index.native.js.map