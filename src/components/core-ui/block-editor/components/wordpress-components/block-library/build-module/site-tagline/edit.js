import _extends from "@babel/runtime/helpers/esm/extends";
import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement, Fragment } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { useEntityProp } from '@wordpress/core-data';
import { AlignmentToolbar, __experimentalUseBlockWrapperProps as useBlockWrapperProps, BlockControls, RichText } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
export default function SiteTaglineEdit(_ref) {
  var attributes = _ref.attributes,
      setAttributes = _ref.setAttributes;
  var textAlign = attributes.textAlign;

  var _useEntityProp = useEntityProp('root', 'site', 'description'),
      _useEntityProp2 = _slicedToArray(_useEntityProp, 2),
      siteTagline = _useEntityProp2[0],
      setSiteTagline = _useEntityProp2[1];

  var blockWrapperProps = useBlockWrapperProps();
  return createElement(Fragment, null, createElement(BlockControls, null, createElement(AlignmentToolbar, {
    onChange: function onChange(newAlign) {
      return setAttributes({
        textAlign: newAlign
      });
    },
    value: textAlign
  })), createElement(RichText, _extends({
    allowedFormats: [],
    className: classnames(_defineProperty({}, "has-text-align-".concat(textAlign), textAlign)),
    onChange: setSiteTagline,
    placeholder: __('Site Tagline'),
    tagName: "p",
    value: siteTagline
  }, blockWrapperProps)));
}
//# sourceMappingURL=edit.js.map