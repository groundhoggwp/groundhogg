"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _panelColorGradientSettings = _interopRequireDefault(require("../colors-gradients/panel-color-gradient-settings"));

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

var PanelColorSettings = function PanelColorSettings(_ref) {
  var colorSettings = _ref.colorSettings,
      props = (0, _objectWithoutProperties2.default)(_ref, ["colorSettings"]);
  var settings = colorSettings.map(function (_ref2) {
    var value = _ref2.value,
        onChange = _ref2.onChange,
        otherSettings = (0, _objectWithoutProperties2.default)(_ref2, ["value", "onChange"]);
    return _objectSpread(_objectSpread({}, otherSettings), {}, {
      colorValue: value,
      onColorChange: onChange
    });
  });
  return (0, _element.createElement)(_panelColorGradientSettings.default, (0, _extends2.default)({
    settings: settings,
    gradients: [],
    disableCustomGradients: true
  }, props));
};

var _default = PanelColorSettings;
exports.default = _default;
//# sourceMappingURL=index.js.map