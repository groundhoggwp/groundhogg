import _extends from "@babel/runtime/helpers/esm/extends";
import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * Internal dependencies
 */
import PanelColorGradientSettings from '../colors-gradients/panel-color-gradient-settings';

var PanelColorSettings = function PanelColorSettings(_ref) {
  var colorSettings = _ref.colorSettings,
      props = _objectWithoutProperties(_ref, ["colorSettings"]);

  var settings = colorSettings.map(function (_ref2) {
    var value = _ref2.value,
        onChange = _ref2.onChange,
        otherSettings = _objectWithoutProperties(_ref2, ["value", "onChange"]);

    return _objectSpread(_objectSpread({}, otherSettings), {}, {
      colorValue: value,
      onColorChange: onChange
    });
  });
  return createElement(PanelColorGradientSettings, _extends({
    settings: settings,
    gradients: [],
    disableCustomGradients: true
  }, props));
};

export default PanelColorSettings;
//# sourceMappingURL=index.js.map