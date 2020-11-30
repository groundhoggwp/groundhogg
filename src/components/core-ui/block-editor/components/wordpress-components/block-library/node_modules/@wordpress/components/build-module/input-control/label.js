import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * Internal dependencies
 */
import VisuallyHidden from '../visually-hidden';
import { Label as BaseLabel } from './styles/input-control-styles';
export default function Label(_ref) {
  var children = _ref.children,
      hideLabelFromVision = _ref.hideLabelFromVision,
      htmlFor = _ref.htmlFor,
      props = _objectWithoutProperties(_ref, ["children", "hideLabelFromVision", "htmlFor"]);

  if (!children) return null;

  if (hideLabelFromVision) {
    return createElement(VisuallyHidden, {
      as: "label",
      htmlFor: htmlFor
    }, children);
  }

  return createElement(BaseLabel, _extends({
    htmlFor: htmlFor
  }, props), children);
}
//# sourceMappingURL=label.js.map