"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _reactNative = require("react-native");

var _compose = require("@wordpress/compose");

var _edit = _interopRequireDefault(require("./edit.js"));

var _styles = _interopRequireDefault(require("./styles.scss"));

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function PreformattedEdit(props) {
  var getStylesFromColorScheme = props.getStylesFromColorScheme;
  var richTextStyle = getStylesFromColorScheme(_styles.default.wpRichTextLight, _styles.default.wpRichTextDark);
  var wpBlockPreformatted = getStylesFromColorScheme(_styles.default.wpBlockPreformattedLight, _styles.default.wpBlockPreformattedDark);

  var propsWithStyle = _objectSpread(_objectSpread({}, props), {}, {
    style: richTextStyle
  });

  return (0, _element.createElement)(_reactNative.View, {
    style: wpBlockPreformatted
  }, (0, _element.createElement)(_edit.default, propsWithStyle));
}

var _default = (0, _compose.withPreferredColorScheme)(PreformattedEdit);

exports.default = _default;
//# sourceMappingURL=edit.native.js.map