import _extends from "@babel/runtime/helpers/esm/extends";
import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement, Fragment } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { __ } from '@wordpress/i18n';
import { RichText, BlockControls, AlignmentToolbar, __experimentalUseBlockWrapperProps as useBlockWrapperProps } from '@wordpress/block-editor';
export default function VerseEdit(_ref) {
  var attributes = _ref.attributes,
      setAttributes = _ref.setAttributes,
      mergeBlocks = _ref.mergeBlocks;
  var textAlign = attributes.textAlign,
      content = attributes.content;
  var blockWrapperProps = useBlockWrapperProps({
    className: classnames(_defineProperty({}, "has-text-align-".concat(textAlign), textAlign))
  });
  return createElement(Fragment, null, createElement(BlockControls, null, createElement(AlignmentToolbar, {
    value: textAlign,
    onChange: function onChange(nextAlign) {
      setAttributes({
        textAlign: nextAlign
      });
    }
  })), createElement(RichText, _extends({
    tagName: "pre",
    identifier: "content",
    preserveWhiteSpace: true,
    value: content,
    onChange: function onChange(nextContent) {
      setAttributes({
        content: nextContent
      });
    },
    placeholder: __('Write…'),
    onMerge: mergeBlocks,
    textAlign: textAlign
  }, blockWrapperProps)));
}
//# sourceMappingURL=edit.js.map