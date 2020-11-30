import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement, Fragment } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * Internal dependencies
 */

import { Mark, MarkLabel } from './styles/range-control-styles';
export default function RangeMark(_ref) {
  var className = _ref.className,
      _ref$isFilled = _ref.isFilled,
      isFilled = _ref$isFilled === void 0 ? false : _ref$isFilled,
      label = _ref.label,
      _ref$style = _ref.style,
      style = _ref$style === void 0 ? {} : _ref$style,
      props = _objectWithoutProperties(_ref, ["className", "isFilled", "label", "style"]);

  var classes = classnames('components-range-control__mark', isFilled && 'is-filled', className);
  var labelClasses = classnames('components-range-control__mark-label', isFilled && 'is-filled');
  return createElement(Fragment, null, createElement(Mark, _extends({}, props, {
    "aria-hidden": "true",
    className: classes,
    isFilled: isFilled,
    style: style
  })), label && createElement(MarkLabel, {
    "aria-hidden": "true",
    className: labelClasses,
    isFilled: isFilled,
    style: style
  }, label));
}
//# sourceMappingURL=mark.js.map