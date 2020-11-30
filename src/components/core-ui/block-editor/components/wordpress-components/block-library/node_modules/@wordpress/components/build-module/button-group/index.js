import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { forwardRef } from '@wordpress/element';

function ButtonGroup(_ref, ref) {
  var className = _ref.className,
      props = _objectWithoutProperties(_ref, ["className"]);

  var classes = classnames('components-button-group', className);
  return createElement("div", _extends({
    ref: ref,
    role: "group",
    className: classes
  }, props));
}

export default forwardRef(ButtonGroup);
//# sourceMappingURL=index.js.map