"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = GradientPickerControl;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _classnames = _interopRequireDefault(require("classnames"));

var _lodash = require("lodash");

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

var _ = _interopRequireDefault(require("./"));

var _useEditorFeature = _interopRequireDefault(require("../use-editor-feature"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function GradientPickerControl(_ref) {
  var className = _ref.className,
      value = _ref.value,
      onChange = _ref.onChange,
      _ref$label = _ref.label,
      label = _ref$label === void 0 ? (0, _i18n.__)('Gradient Presets') : _ref$label,
      props = (0, _objectWithoutProperties2.default)(_ref, ["className", "value", "onChange", "label"]);
  var gradients = (0, _useEditorFeature.default)('color.gradients');
  var disableCustomGradients = !(0, _useEditorFeature.default)('color.customGradient');

  if ((0, _lodash.isEmpty)(gradients) && disableCustomGradients) {
    return null;
  }

  return (0, _element.createElement)(_components.BaseControl, {
    className: (0, _classnames.default)('block-editor-gradient-picker-control', className)
  }, (0, _element.createElement)(_components.BaseControl.VisualLabel, null, label), (0, _element.createElement)(_.default, (0, _extends2.default)({
    value: value,
    onChange: onChange,
    className: "block-editor-gradient-picker-control__gradient-picker-presets",
    gradients: gradients,
    disableCustomGradients: disableCustomGradients
  }, props)));
}
//# sourceMappingURL=control.js.map