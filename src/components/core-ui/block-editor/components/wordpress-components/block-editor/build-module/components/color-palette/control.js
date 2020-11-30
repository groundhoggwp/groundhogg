import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * Internal dependencies
 */
import ColorGradientControl from '../colors-gradients/control';
export default function ColorPaletteControl(_ref) {
  var onChange = _ref.onChange,
      value = _ref.value,
      otherProps = _objectWithoutProperties(_ref, ["onChange", "value"]);

  return createElement(ColorGradientControl, _extends({}, otherProps, {
    onColorChange: onChange,
    colorValue: value,
    gradients: [],
    disableCustomGradients: true
  }));
}
//# sourceMappingURL=control.js.map