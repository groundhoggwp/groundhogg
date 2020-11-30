"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _reactNative = require("react-native");

var _components = require("@wordpress/components");

var _compose = require("@wordpress/compose");

var _actions = _interopRequireDefault(require("./actions.scss"));

var _bottomSeparatorCover = _interopRequireDefault(require("./bottom-separator-cover"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function PanelActions(_ref) {
  var actions = _ref.actions,
      getStylesFromColorScheme = _ref.getStylesFromColorScheme;
  return (0, _element.createElement)(_reactNative.View, {
    style: getStylesFromColorScheme(_actions.default.panelActionsContainer, _actions.default.panelActionsContainerDark)
  }, actions.map(function (_ref2) {
    var label = _ref2.label,
        onPress = _ref2.onPress;
    return (0, _element.createElement)(_components.TextControl, {
      label: label,
      onPress: onPress,
      labelStyle: _actions.default.defaultLabelStyle,
      key: label
    });
  }), (0, _element.createElement)(_bottomSeparatorCover.default, null));
}

var _default = (0, _compose.withPreferredColorScheme)(PanelActions);

exports.default = _default;
//# sourceMappingURL=actions.native.js.map