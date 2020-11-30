"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = exports.KeyboardAwareFlatList = void 0;

var _element = require("@wordpress/element");

var _reactNative = require("react-native");

var _keyboardAvoidingView = _interopRequireDefault(require("../keyboard-avoiding-view"));

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
var KeyboardAwareFlatList = function KeyboardAwareFlatList(props) {
  return (0, _element.createElement)(_keyboardAvoidingView.default, {
    style: {
      flex: 1
    }
  }, (0, _element.createElement)(_reactNative.FlatList, props));
};

exports.KeyboardAwareFlatList = KeyboardAwareFlatList;

KeyboardAwareFlatList.handleCaretVerticalPositionChange = function () {//no need to handle on Android, it is system managed
};

var _default = KeyboardAwareFlatList;
exports.default = _default;
//# sourceMappingURL=index.android.js.map