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

import { CardContext } from './context';
import { CardUI } from './styles/card-styles';
export var defaultProps = {
  isBorderless: false,
  isElevated: false,
  size: 'medium'
};
export function Card(props) {
  var className = props.className,
      isBorderless = props.isBorderless,
      isElevated = props.isElevated,
      size = props.size,
      additionalProps = _objectWithoutProperties(props, ["className", "isBorderless", "isElevated", "size"]);

  var Provider = CardContext.Provider;
  var contextProps = {
    isBorderless: isBorderless,
    isElevated: isElevated,
    size: size
  };
  var classes = classnames('components-card', isBorderless && 'is-borderless', isElevated && 'is-elevated', size && "is-size-".concat(size), className);
  return createElement(Provider, {
    value: contextProps
  }, createElement(CardUI, _extends({}, additionalProps, {
    className: classes
  })));
}
Card.defaultProps = defaultProps;
export default Card;
//# sourceMappingURL=index.js.map