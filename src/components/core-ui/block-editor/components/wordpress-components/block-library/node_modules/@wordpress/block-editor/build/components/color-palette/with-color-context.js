"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _lodash = require("lodash");

var _compose = require("@wordpress/compose");

var _useEditorFeature = _interopRequireDefault(require("../use-editor-feature"));

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

var _default = (0, _compose.createHigherOrderComponent)(function (WrappedComponent) {
  return function (props) {
    var colorsFeature = (0, _useEditorFeature.default)('color.palette');
    var disableCustomColorsFeature = !(0, _useEditorFeature.default)('color.custom');
    var colors = props.colors === undefined ? colorsFeature : props.colors;
    var disableCustomColors = props.disableCustomColors === undefined ? disableCustomColorsFeature : props.disableCustomColors;
    var hasColorsToChoose = !(0, _lodash.isEmpty)(colors) || !disableCustomColors;
    return (0, _element.createElement)(WrappedComponent, _objectSpread(_objectSpread({}, props), {}, {
      colors: colors,
      disableCustomColors: disableCustomColors,
      hasColorsToChoose: hasColorsToChoose
    }));
  };
}, 'withColorContext');

exports.default = _default;
//# sourceMappingURL=with-color-context.js.map