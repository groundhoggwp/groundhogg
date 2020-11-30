import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import deprecated from '@wordpress/deprecated';
import { forwardRef } from '@wordpress/element';
/**
 * Internal dependencies
 */

import Button from '../button';

function IconButton(_ref, ref) {
  var labelPosition = _ref.labelPosition,
      size = _ref.size,
      tooltip = _ref.tooltip,
      label = _ref.label,
      props = _objectWithoutProperties(_ref, ["labelPosition", "size", "tooltip", "label"]);

  deprecated('wp.components.IconButton', {
    alternative: 'wp.components.Button'
  });
  return createElement(Button, _extends({}, props, {
    ref: ref,
    tooltipPosition: labelPosition,
    iconSize: size,
    showTooltip: tooltip !== undefined ? !!tooltip : undefined,
    label: tooltip || label
  }));
}

export default forwardRef(IconButton);
//# sourceMappingURL=deprecated.js.map