import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { map } from 'lodash';
/**
 * Internal dependencies
 */

import PanelColorSettings from '../panel-color-settings';
import ContrastChecker from '../contrast-checker';

var resolveContrastCheckerColor = function resolveContrastCheckerColor(color, colorSettings, detectedColor) {
  if (typeof color === 'function') {
    return color(colorSettings);
  } else if (color === true) {
    return detectedColor;
  }

  return color;
};

export default function ColorPanel(_ref) {
  var title = _ref.title,
      colorSettings = _ref.colorSettings,
      colorPanelProps = _ref.colorPanelProps,
      contrastCheckers = _ref.contrastCheckers,
      detectedBackgroundColor = _ref.detectedBackgroundColor,
      detectedColor = _ref.detectedColor,
      panelChildren = _ref.panelChildren,
      initialOpen = _ref.initialOpen;
  return createElement(PanelColorSettings, _extends({
    title: title,
    initialOpen: initialOpen,
    colorSettings: Object.values(colorSettings)
  }, colorPanelProps), contrastCheckers && (Array.isArray(contrastCheckers) ? contrastCheckers.map(function (_ref2) {
    var backgroundColor = _ref2.backgroundColor,
        textColor = _ref2.textColor,
        rest = _objectWithoutProperties(_ref2, ["backgroundColor", "textColor"]);

    backgroundColor = resolveContrastCheckerColor(backgroundColor, colorSettings, detectedBackgroundColor);
    textColor = resolveContrastCheckerColor(textColor, colorSettings, detectedColor);
    return createElement(ContrastChecker, _extends({
      key: "".concat(backgroundColor, "-").concat(textColor),
      backgroundColor: backgroundColor,
      textColor: textColor
    }, rest));
  }) : map(colorSettings, function (_ref3) {
    var value = _ref3.value;
    var backgroundColor = contrastCheckers.backgroundColor,
        textColor = contrastCheckers.textColor;
    backgroundColor = resolveContrastCheckerColor(backgroundColor || value, colorSettings, detectedBackgroundColor);
    textColor = resolveContrastCheckerColor(textColor || value, colorSettings, detectedColor);
    return createElement(ContrastChecker, _extends({}, contrastCheckers, {
      key: "".concat(backgroundColor, "-").concat(textColor),
      backgroundColor: backgroundColor,
      textColor: textColor
    }));
  })), typeof panelChildren === 'function' ? panelChildren(colorSettings) : panelChildren);
}
//# sourceMappingURL=color-panel.js.map