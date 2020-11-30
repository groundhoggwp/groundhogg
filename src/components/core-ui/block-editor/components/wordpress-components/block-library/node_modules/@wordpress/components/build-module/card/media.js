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

import { MediaUI } from './styles/card-styles';
export function CardMedia(props) {
  var className = props.className,
      additionalProps = _objectWithoutProperties(props, ["className"]);

  var classes = classnames('components-card__media', className);
  return createElement(MediaUI, _extends({}, additionalProps, {
    className: classes
  }));
}
export default CardMedia;
//# sourceMappingURL=media.js.map