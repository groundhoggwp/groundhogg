"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _reactNative = require("react-native");

var _baseControl = _interopRequireDefault(require("../base-control"));

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
function TextareaControl(_ref) {
  var label = _ref.label,
      value = _ref.value,
      help = _ref.help,
      onChange = _ref.onChange,
      _ref$rows = _ref.rows,
      rows = _ref$rows === void 0 ? 4 : _ref$rows;
  return (0, _element.createElement)(_baseControl.default, {
    label: label,
    help: help
  }, (0, _element.createElement)(_reactNative.TextInput, {
    style: {
      height: 80,
      borderColor: 'gray',
      borderWidth: 1
    },
    value: value,
    onChangeText: onChange,
    numberOfLines: rows,
    multiline: rows > 1,
    textAlignVertical: "top"
  }));
}

var _default = TextareaControl;
exports.default = _default;
//# sourceMappingURL=index.native.js.map