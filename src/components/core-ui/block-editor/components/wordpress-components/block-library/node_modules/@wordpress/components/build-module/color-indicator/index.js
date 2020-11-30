import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';

var ColorIndicator = function ColorIndicator(_ref) {
  var className = _ref.className,
      colorValue = _ref.colorValue,
      props = _objectWithoutProperties(_ref, ["className", "colorValue"]);

  return createElement("span", _extends({
    className: classnames('component-color-indicator', className),
    style: {
      background: colorValue
    }
  }, props));
};

export default ColorIndicator;
//# sourceMappingURL=index.js.map