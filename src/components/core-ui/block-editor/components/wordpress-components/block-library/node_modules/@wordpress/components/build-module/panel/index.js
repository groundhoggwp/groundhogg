import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * Internal dependencies
 */

import PanelHeader from './header';

function Panel(_ref) {
  var header = _ref.header,
      className = _ref.className,
      children = _ref.children;
  var classNames = classnames(className, 'components-panel');
  return createElement("div", {
    className: classNames
  }, header && createElement(PanelHeader, {
    label: header
  }), children);
}

export default Panel;
//# sourceMappingURL=index.js.map