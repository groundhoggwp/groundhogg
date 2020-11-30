import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement, Fragment } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { __experimentalSearchForm as SearchForm } from '@wordpress/block-editor';
import { useState } from '@wordpress/element';
/**
 * Internal dependencies
 */

import TemplatePartPreviews from './template-part-previews';
export default function TemplatePartSelection(_ref) {
  var setAttributes = _ref.setAttributes,
      onClose = _ref.onClose;

  var _useState = useState(''),
      _useState2 = _slicedToArray(_useState, 2),
      filterValue = _useState2[0],
      setFilterValue = _useState2[1];

  return createElement(Fragment, null, createElement(SearchForm, {
    value: filterValue,
    onChange: setFilterValue,
    className: "wp-block-template-part__selection-preview-search-form"
  }), createElement("div", {
    className: "wp-block-template-part__selection-preview-container"
  }, createElement(TemplatePartPreviews, {
    setAttributes: setAttributes,
    filterValue: filterValue,
    onClose: onClose
  })));
}
//# sourceMappingURL=index.js.map