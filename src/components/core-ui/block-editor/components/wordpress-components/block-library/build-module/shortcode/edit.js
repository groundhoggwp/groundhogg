import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PlainText } from '@wordpress/block-editor';
import { useInstanceId } from '@wordpress/compose';
import { Icon, shortcode } from '@wordpress/icons';
export default function ShortcodeEdit(_ref) {
  var attributes = _ref.attributes,
      setAttributes = _ref.setAttributes;
  var instanceId = useInstanceId(ShortcodeEdit);
  var inputId = "blocks-shortcode-input-".concat(instanceId);
  return createElement("div", {
    className: "wp-block-shortcode components-placeholder"
  }, createElement("label", {
    htmlFor: inputId,
    className: "components-placeholder__label"
  }, createElement(Icon, {
    icon: shortcode
  }), __('Shortcode')), createElement(PlainText, {
    className: "blocks-shortcode__textarea",
    id: inputId,
    value: attributes.text,
    placeholder: __('Write shortcode here…'),
    onChange: function onChange(text) {
      return setAttributes({
        text: text
      });
    }
  }));
}
//# sourceMappingURL=edit.js.map