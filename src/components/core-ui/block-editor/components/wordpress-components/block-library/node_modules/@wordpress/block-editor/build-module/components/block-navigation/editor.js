import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

import { RichText } from '../';
import { BlockNavigationBlockFill } from './block-slot';
export default function BlockNavigationEditor(_ref) {
  var value = _ref.value,
      onChange = _ref.onChange;
  return createElement(BlockNavigationBlockFill, null, createElement(RichText, {
    value: value,
    onChange: onChange,
    placeholder: __('Navigation item'),
    keepPlaceholderOnFocus: true,
    withoutInteractiveFormatting: true,
    allowedFormats: ['core/bold', 'core/italic', 'core/image', 'core/strikethrough']
  }));
}
//# sourceMappingURL=editor.js.map