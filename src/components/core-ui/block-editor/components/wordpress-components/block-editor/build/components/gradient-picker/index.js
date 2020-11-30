"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = _default;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _components = require("@wordpress/components");

var _useEditorFeature = _interopRequireDefault(require("../use-editor-feature"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var EMPTY_ARRAY = [];

function GradientPickerWithGradients(props) {
  var gradients = (0, _useEditorFeature.default)('color.gradients') || EMPTY_ARRAY;
  var disableCustomGradients = !(0, _useEditorFeature.default)('color.customGradient');
  return (0, _element.createElement)(_components.__experimentalGradientPicker, (0, _extends2.default)({
    gradients: props.gradients !== undefined ? props.gradient : gradients,
    disableCustomGradients: props.disableCustomGradients !== undefined ? props.disableCustomGradients : disableCustomGradients
  }, props));
}

function _default(props) {
  var ComponentToUse = props.gradients !== undefined && props.disableCustomGradients !== undefined ? _components.__experimentalGradientPicker : GradientPickerWithGradients;
  return (0, _element.createElement)(ComponentToUse, props);
}
//# sourceMappingURL=index.js.map