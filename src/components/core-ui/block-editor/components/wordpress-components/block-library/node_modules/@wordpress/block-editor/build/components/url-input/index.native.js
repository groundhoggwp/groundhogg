"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = URLInput;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _reactNative = require("react-native");

var _i18n = require("@wordpress/i18n");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
function URLInput(_ref) {
  var _ref$value = _ref.value,
      value = _ref$value === void 0 ? '' : _ref$value,
      _ref$autoFocus = _ref.autoFocus,
      autoFocus = _ref$autoFocus === void 0 ? true : _ref$autoFocus,
      onChange = _ref.onChange,
      extraProps = (0, _objectWithoutProperties2.default)(_ref, ["value", "autoFocus", "onChange"]);

  /* eslint-disable jsx-a11y/no-autofocus */
  return (0, _element.createElement)(_reactNative.TextInput, (0, _extends2.default)({
    autoFocus: autoFocus,
    editable: true,
    selectTextOnFocus: true,
    autoCapitalize: "none",
    autoCorrect: false,
    textContentType: "URL",
    value: value,
    onChangeText: onChange,
    placeholder: (0, _i18n.__)('Paste URL')
  }, extraProps));
  /* eslint-enable jsx-a11y/no-autofocus */
}
//# sourceMappingURL=index.native.js.map