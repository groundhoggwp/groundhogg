import { createElement, Fragment } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import deprecated from '@wordpress/deprecated';
import { __ } from '@wordpress/i18n';
import { RichText, BlockControls, AlignmentToolbar } from '@wordpress/block-editor';
export default function SubheadEdit(_ref) {
  var attributes = _ref.attributes,
      setAttributes = _ref.setAttributes,
      className = _ref.className;
  var align = attributes.align,
      content = attributes.content,
      placeholder = attributes.placeholder;
  deprecated('The Subheading block', {
    alternative: 'the Paragraph block',
    plugin: 'Gutenberg'
  });
  return createElement(Fragment, null, createElement(BlockControls, null, createElement(AlignmentToolbar, {
    value: align,
    onChange: function onChange(nextAlign) {
      setAttributes({
        align: nextAlign
      });
    }
  })), createElement(RichText, {
    tagName: "p",
    value: content,
    onChange: function onChange(nextContent) {
      setAttributes({
        content: nextContent
      });
    },
    style: {
      textAlign: align
    },
    className: className,
    placeholder: placeholder || __('Write subheading…')
  }));
}
//# sourceMappingURL=edit.js.map