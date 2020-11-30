import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
/**
 * Internal dependencies
 */

import PanelColorGradientSettings from '../components/colors-gradients/panel-color-gradient-settings';
import ContrastChecker from '../components/contrast-checker';
import InspectorControls from '../components/inspector-controls';
import { getBlockDOMNode } from '../utils/dom';

function getComputedStyle(node) {
  return node.ownerDocument.defaultView.getComputedStyle(node);
}

export default function ColorPanel(_ref) {
  var settings = _ref.settings,
      clientId = _ref.clientId,
      _ref$enableContrastCh = _ref.enableContrastChecking,
      enableContrastChecking = _ref$enableContrastCh === void 0 ? true : _ref$enableContrastCh;

  var _useState = useState(),
      _useState2 = _slicedToArray(_useState, 2),
      detectedBackgroundColor = _useState2[0],
      setDetectedBackgroundColor = _useState2[1];

  var _useState3 = useState(),
      _useState4 = _slicedToArray(_useState3, 2),
      detectedColor = _useState4[0],
      setDetectedColor = _useState4[1];

  useEffect(function () {
    if (!enableContrastChecking) {
      return;
    }

    var colorsDetectionElement = getBlockDOMNode(clientId);

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
  return createElement(InspectorControls, null, createElement(PanelColorGradientSettings, {
    title: __('Color settings'),
    initialOpen: false,
    settings: settings
  }, enableContrastChecking && createElement(ContrastChecker, {
    backgroundColor: detectedBackgroundColor,
    textColor: detectedColor
  })));
}
//# sourceMappingURL=color-panel.js.map