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

import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { keyboardReturn } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import URLInput from '../url-input';
export default function LinkEditor(_ref) {
  var autocompleteRef = _ref.autocompleteRef,
      className = _ref.className,
      onChangeInputValue = _ref.onChangeInputValue,
      value = _ref.value,
      props = _objectWithoutProperties(_ref, ["autocompleteRef", "className", "onChangeInputValue", "value"]);

  return createElement("form", _extends({
    className: classnames('block-editor-url-popover__link-editor', className)
  }, props), createElement(URLInput, {
    value: value,
    onChange: onChangeInputValue,
    autocompleteRef: autocompleteRef
  }), createElement(Button, {
    icon: keyboardReturn,
    label: __('Apply'),
    type: "submit"
  }));
}
//# sourceMappingURL=link-editor.js.map