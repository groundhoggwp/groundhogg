import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { MenuItem } from '@wordpress/components';
export default function BlockConvertButton(_ref) {
  var shouldRender = _ref.shouldRender,
      onClick = _ref.onClick,
      small = _ref.small;

  if (!shouldRender) {
    return null;
  }

  var label = __('Convert to Blocks');

  return createElement(MenuItem, {
    onClick: onClick
  }, !small && label);
}
//# sourceMappingURL=block-convert-button.js.map