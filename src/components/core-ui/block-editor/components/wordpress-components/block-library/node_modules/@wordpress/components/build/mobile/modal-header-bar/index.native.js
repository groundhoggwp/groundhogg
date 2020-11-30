"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _reactNative = require("react-native");

var _compose = require("@wordpress/compose");

var _styles = _interopRequireDefault(require("./styles.scss"));

var _button = require("./button");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var ModalHeaderBar = (0, _compose.withPreferredColorScheme)(function (props) {
  var leftButton = props.leftButton,
      title = props.title,
      subtitle = props.subtitle,
      rightButton = props.rightButton,
      getStylesFromColorScheme = props.getStylesFromColorScheme;
  var separatorStyle = getStylesFromColorScheme(_styles.default.separator, _styles.default.separatorDark);
  var titleStyle = getStylesFromColorScheme(_styles.default.title, _styles.default.titleDark);
  var subtitleStyle = getStylesFromColorScheme(_styles.default.subtitle, _styles.default.subtitleDark);
  return (0, _element.createElement)(_reactNative.View, null, (0, _element.createElement)(_reactNative.View, {
    style: [_styles.default.bar, subtitle && _styles.default.subtitleBar]
  }, (0, _element.createElement)(_reactNative.View, {
    style: _styles.default.leftContainer
  }, leftButton), (0, _element.createElement)(_reactNative.View, {
    style: _styles.default.titleContainer,
    accessibilityRole: "header"
  }, (0, _element.createElement)(_reactNative.Text, {
    style: titleStyle
  }, title), subtitle && (0, _element.createElement)(_reactNative.Text, {
    style: subtitleStyle
  }, subtitle)), (0, _element.createElement)(_reactNative.View, {
    style: _styles.default.rightContainer
  }, rightButton)), (0, _element.createElement)(_reactNative.View, {
    style: separatorStyle
  }));
});
ModalHeaderBar.displayName = 'ModalHeaderBar';
ModalHeaderBar.Button = _button.Button;
ModalHeaderBar.CloseButton = _button.CloseButton;
var _default = ModalHeaderBar;
exports.default = _default;
//# sourceMappingURL=index.native.js.map