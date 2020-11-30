"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = ColorPanel;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _lodash = require("lodash");

var _panelColorSettings = _interopRequireDefault(require("../panel-color-settings"));

var _contrastChecker = _interopRequireDefault(require("../contrast-checker"));

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
var resolveContrastCheckerColor = function resolveContrastCheckerColor(color, colorSettings, detectedColor) {
  if (typeof color === 'function') {
    return color(colorSettings);
  } else if (color === true) {
    return detectedColor;
  }

  return color;
};

function ColorPanel(_ref) {
  var title = _ref.title,
      colorSettings = _ref.colorSettings,
      colorPanelProps = _ref.colorPanelProps,
      contrastCheckers = _ref.contrastCheckers,
      detectedBackgroundColor = _ref.detectedBackgroundColor,
      detectedColor = _ref.detectedColor,
      panelChildren = _ref.panelChildren,
      initialOpen = _ref.initialOpen;
  return (0, _element.createElement)(_panelColorSettings.default, (0, _extends2.default)({
    title: title,
    initialOpen: initialOpen,
    colorSettings: Object.values(colorSettings)
  }, colorPanelProps), contrastCheckers && (Array.isArray(contrastCheckers) ? contrastCheckers.map(function (_ref2) {
    var backgroundColor = _ref2.backgroundColor,
        textColor = _ref2.textColor,
        rest = (0, _objectWithoutProperties2.default)(_ref2, ["backgroundColor", "textColor"]);
    backgroundColor = resolveContrastCheckerColor(backgroundColor, colorSettings, detectedBackgroundColor);
    textColor = resolveContrastCheckerColor(textColor, colorSettings, detectedColor);
    return (0, _element.createElement)(_contrastChecker.default, (0, _extends2.default)({
      key: "".concat(backgroundColor, "-").concat(textColor),
      backgroundColor: backgroundColor,
      textColor: textColor
    }, rest));
  }) : (0, _lodash.map)(colorSettings, function (_ref3) {
    var value = _ref3.value;
    var backgroundColor = contrastCheckers.backgroundColor,
        textColor = contrastCheckers.textColor;
    backgroundColor = resolveContrastCheckerColor(backgroundColor || value, colorSettings, detectedBackgroundColor);
    textColor = resolveContrastCheckerColor(textColor || value, colorSettings, detectedColor);
    return (0, _element.createElement)(_contrastChecker.default, (0, _extends2.default)({}, contrastCheckers, {
      key: "".concat(backgroundColor, "-").concat(textColor),
      backgroundColor: backgroundColor,
      textColor: textColor
    }));
  })), typeof panelChildren === 'function' ? panelChildren(colorSettings) : panelChildren);
}
//# sourceMappingURL=color-panel.js.map