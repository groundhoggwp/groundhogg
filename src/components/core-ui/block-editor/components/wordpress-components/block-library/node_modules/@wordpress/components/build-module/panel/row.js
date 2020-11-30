import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';

function PanelRow(_ref) {
  var className = _ref.className,
      children = _ref.children;
  var classes = classnames('components-panel__row', className);
  return createElement("div", {
    className: classes
  }, children);
}

export default PanelRow;
//# sourceMappingURL=row.js.map