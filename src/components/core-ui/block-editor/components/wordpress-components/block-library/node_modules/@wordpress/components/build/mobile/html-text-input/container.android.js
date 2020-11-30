"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _reactNative = require("react-native");

var _keyboardAvoidingView = _interopRequireDefault(require("../keyboard-avoiding-view"));

var _styleAndroid = _interopRequireDefault(require("./style.android.scss"));

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
var HTMLInputContainer = function HTMLInputContainer(_ref) {
  var children = _ref.children,
      parentHeight = _ref.parentHeight;
  return (0, _element.createElement)(_keyboardAvoidingView.default, {
    style: _styleAndroid.default.keyboardAvoidingView,
    parentHeight: parentHeight
  }, (0, _element.createElement)(_reactNative.ScrollView, {
    style: _styleAndroid.default.scrollView
  }, children));
};

HTMLInputContainer.scrollEnabled = false;
var _default = HTMLInputContainer;
exports.default = _default;
//# sourceMappingURL=container.android.js.map