import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

import Warning from '../warning';
export default function BlockInvalidWarning(_ref) {
  var blockTitle = _ref.blockTitle,
      icon = _ref.icon;
  var accessibilityLabel = sprintf(
  /* translators: accessibility text for blocks with invalid content. %d: localized block title */
  __('%s block. This block has invalid content'), blockTitle);
  return createElement(Warning, {
    title: blockTitle,
    message: __('Problem displaying block'),
    icon: icon,
    accessible: true,
    accessibilityLabel: accessibilityLabel
  });
}
//# sourceMappingURL=block-invalid-warning.native.js.map