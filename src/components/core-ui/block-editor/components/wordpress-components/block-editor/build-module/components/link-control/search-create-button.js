import _extends from "@babel/runtime/helpers/esm/extends";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
import { isFunction } from 'lodash';
/**
 * WordPress dependencies
 */

import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { Icon, plusCircle } from '@wordpress/icons';
export var LinkControlSearchCreate = function LinkControlSearchCreate(_ref) {
  var searchTerm = _ref.searchTerm,
      onClick = _ref.onClick,
      itemProps = _ref.itemProps,
      isSelected = _ref.isSelected,
      buttonText = _ref.buttonText;

  if (!searchTerm) {
    return null;
  }

  var text;

  if (buttonText) {
    text = isFunction(buttonText) ? buttonText(searchTerm) : buttonText;
  } else {
    text = createInterpolateElement(sprintf(
    /* translators: %s: search term. */
    __('Create: <mark>%s</mark>'), searchTerm), {
      mark: createElement("mark", null)
    });
  }

  return createElement(Button, _extends({}, itemProps, {
    className: classnames('block-editor-link-control__search-create block-editor-link-control__search-item', {
      'is-selected': isSelected
    }),
    onClick: onClick
  }), createElement(Icon, {
    className: "block-editor-link-control__search-item-icon",
    icon: plusCircle
  }), createElement("span", {
    className: "block-editor-link-control__search-item-header"
  }, createElement("span", {
    className: "block-editor-link-control__search-item-title"
  }, text)));
};
export default LinkControlSearchCreate;
//# sourceMappingURL=search-create-button.js.map