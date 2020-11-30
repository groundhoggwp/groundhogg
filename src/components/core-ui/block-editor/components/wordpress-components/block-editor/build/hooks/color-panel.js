"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = ColorPanel;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _i18n = require("@wordpress/i18n");

var _panelColorGradientSettings = _interopRequireDefault(require("../components/colors-gradients/panel-color-gradient-settings"));

var _contrastChecker = _interopRequireDefault(require("../components/contrast-checker"));

var _inspectorControls = _interopRequireDefault(require("../components/inspector-controls"));

var _dom = require("../utils/dom");

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function getComputedStyle(node) {
  return node.ownerDocument.defaultView.getComputedStyle(node);
}

function ColorPanel(_ref) {
  var settings = _ref.settings,
      clientId = _ref.clientId,
      _ref$enableContrastCh = _ref.enableContrastChecking,
      enableContrastChecking = _ref$enableContrastCh === void 0 ? true : _ref$enableContrastCh;

  var _useState = (0, _element.useState)(),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      detectedBackgroundColor = _useState2[0],
      setDetectedBackgroundColor = _useState2[1];

  var _useState3 = (0, _element.useState)(),
      _useState4 = (0, _slicedToArray2.default)(_useState3, 2),
      detectedColor = _useState4[0],
      setDetectedColor = _useState4[1];

  (0, _element.useEffect)(function () {
    if (!enableContrastChecking) {
      return;
    }

    var colorsDetectionElement = (0, _dom.getBlockDOMNode)(clientId);

    if (!colorsDetectionElement) {
      return;
    }

    setDetectedColor(getComputedStyle(colorsDetectionElement).color);
    var backgroundColorNode = colorsDetectionElement;
    var backgroundColor = getComputedStyle(backgroundColorNode).backgroundColor;

    while (backgroundColor === 'rgba(0, 0, 0, 0)' && backgroundColorNode.parentNode && backgroundColorNode.parentNode.nodeType === backgroundColorNode.parentNode.ELEMENT_NODE) {
      backgroundColorNode = backgroundColorNode.parentNode;
      backgroundColor = getComputedStyle(backgroundColorNode).backgroundColor;
    }

    setDetectedBackgroundColor(backgroundColor);
  });
  return (0, _element.createElement)(_inspectorControls.default, null, (0, _element.createElement)(_panelColorGradientSettings.default, {
    title: (0, _i18n.__)('Color settings'),
    initialOpen: false,
    settings: settings
  }, enableContrastChecking && (0, _element.createElement)(_contrastChecker.default, {
    backgroundColor: detectedBackgroundColor,
    textColor: detectedColor
  })));
}
//# sourceMappingURL=color-panel.js.map