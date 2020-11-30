import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { __ } from '@wordpress/i18n';
import { Button, ExternalLink } from '@wordpress/components';
import { filterURLForDisplay, safeDecodeURI } from '@wordpress/url';
/**
 * Internal dependencies
 */

import { ViewerSlot } from './viewer-slot';
export default function LinkPreview(_ref) {
  var value = _ref.value,
      onEditClick = _ref.onEditClick;
  var displayURL = value && filterURLForDisplay(safeDecodeURI(value.url)) || '';
  return createElement("div", {
    "aria-label": __('Currently selected'),
    "aria-selected": "true",
    className: classnames('block-editor-link-control__search-item', {
      'is-current': true
    })
  }, createElement("span", {
    className: "block-editor-link-control__search-item-header"
  }, createElement(ExternalLink, {
    className: "block-editor-link-control__search-item-title",
    href: value.url
  }, value && value.title || displayURL), value && value.title && createElement("span", {
    className: "block-editor-link-control__search-item-info"
  }, displayURL)), createElement(Button, {
    isSecondary: true,
    onClick: function onClick() {
      return onEditClick();
    },
    className: "block-editor-link-control__search-item-action"
  }, __('Edit')), createElement(ViewerSlot, {
    fillProps: value
  }));
}
//# sourceMappingURL=link-preview.js.map