import _extends from "@babel/runtime/helpers/esm/extends";
import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { RichText, __experimentalUseBlockWrapperProps as useBlockWrapperProps } from '@wordpress/block-editor';
export default function PreformattedEdit(_ref) {
  var attributes = _ref.attributes,
      mergeBlocks = _ref.mergeBlocks,
      setAttributes = _ref.setAttributes;
  var content = attributes.content;
  var blockWrapperProps = useBlockWrapperProps();
  return createElement(RichText, _extends({
    tagName: "pre",
    identifier: "content",
    preserveWhiteSpace: true,
    value: content,
    onChange: function onChange(nextContent) {
      setAttributes({
        content: nextContent
      });
    },
    placeholder: __('Write preformatted text…'),
    onMerge: mergeBlocks
  }, blockWrapperProps));
}
//# sourceMappingURL=edit.js.map