"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _reactNative = require("react-native");

var _compose = require("@wordpress/compose");

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
function ResizableBox(props) {
  var size = props.size,
      _props$showHandle = props.showHandle,
      showHandle = _props$showHandle === void 0 ? true : _props$showHandle,
      getStylesFromColorScheme = props.getStylesFromColorScheme;
  var height = size.height;
  var defaultStyle = getStylesFromColorScheme(_style.default.staticSpacer, _style.default.staticDarkSpacer);
  return (0, _element.createElement)(_reactNative.View, {
    style: [defaultStyle, showHandle && _style.default.selectedSpacer, {
      height: height
    }]
  });
}

var _default = (0, _compose.withPreferredColorScheme)(ResizableBox);

exports.default = _default;
//# sourceMappingURL=index.native.js.map