import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * Internal dependencies
 */

import { DividerUI } from './styles/card-styles';
export function CardDivider(props) {
  var className = props.className,
      additionalProps = _objectWithoutProperties(props, ["className"]);

  var classes = classnames('components-card__divider', className);
  return createElement(DividerUI, _extends({}, additionalProps, {
    children: null,
    className: classes,
    role: "separator"
  }));
}
export default CardDivider;
//# sourceMappingURL=divider.js.map