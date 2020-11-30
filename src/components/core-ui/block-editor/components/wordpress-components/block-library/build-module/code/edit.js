import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { RichText, __experimentalUseBlockWrapperProps as useBlockWrapperProps } from '@wordpress/block-editor';
export default function CodeEdit(_ref) {
  var attributes = _ref.attributes,
      setAttributes = _ref.setAttributes;
  var blockWrapperProps = useBlockWrapperProps();
  return createElement("pre", blockWrapperProps, createElement(RichText, {
    tagName: "code",
    value: attributes.content,
    onChange: function onChange(content) {
      return setAttributes({
        content: content
      });
    },
    placeholder: __('Write code…'),
    "aria-label": __('Code')
  }));
}
//# sourceMappingURL=edit.js.map