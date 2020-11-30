import _extends from "@babel/runtime/helpers/esm/extends";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { safeDecodeURI, filterURLForDisplay } from '@wordpress/url';
import { __ } from '@wordpress/i18n';
import { Button, TextHighlight } from '@wordpress/components';
import { Icon, globe } from '@wordpress/icons';
export var LinkControlSearchItem = function LinkControlSearchItem(_ref) {
  var itemProps = _ref.itemProps,
      suggestion = _ref.suggestion,
      _ref$isSelected = _ref.isSelected,
      isSelected = _ref$isSelected === void 0 ? false : _ref$isSelected,
      onClick = _ref.onClick,
      _ref$isURL = _ref.isURL,
      isURL = _ref$isURL === void 0 ? false : _ref$isURL,
      _ref$searchTerm = _ref.searchTerm,
      searchTerm = _ref$searchTerm === void 0 ? '' : _ref$searchTerm,
      _ref$shouldShowType = _ref.shouldShowType,
      shouldShowType = _ref$shouldShowType === void 0 ? false : _ref$shouldShowType;
  return createElement(Button, _extends({}, itemProps, {
    onClick: onClick,
    className: classnames('block-editor-link-control__search-item', {
      'is-selected': isSelected,
      'is-url': isURL,
      'is-entity': !isURL
    })
  }), isURL && createElement(Icon, {
    className: "block-editor-link-control__search-item-icon",
    icon: globe
  }), createElement("span", {
    className: "block-editor-link-control__search-item-header"
  }, createElement("span", {
    className: "block-editor-link-control__search-item-title"
  }, createElement(TextHighlight, {
    text: suggestion.title,
    highlight: searchTerm
  })), createElement("span", {
    "aria-hidden": !isURL,
    className: "block-editor-link-control__search-item-info"
  }, !isURL && (filterURLForDisplay(safeDecodeURI(suggestion.url)) || ''), isURL && __('Press ENTER to add this link'))), shouldShowType && suggestion.type && createElement("span", {
    className: "block-editor-link-control__search-item-type"
  }, suggestion.type === 'post_tag' ? 'tag' : suggestion.type));
};
export default LinkControlSearchItem;
//# sourceMappingURL=search-item.js.map