import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { useEntityProp } from '@wordpress/core-data';
import { TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { cleanForSlug } from '@wordpress/url';
export default function TemplatePartNamePanel(_ref) {
  var postId = _ref.postId,
      setAttributes = _ref.setAttributes;

  var _useEntityProp = useEntityProp('postType', 'wp_template_part', 'title', postId),
      _useEntityProp2 = _slicedToArray(_useEntityProp, 2),
      title = _useEntityProp2[0],
      setTitle = _useEntityProp2[1];

  var _useEntityProp3 = useEntityProp('postType', 'wp_template_part', 'slug', postId),
      _useEntityProp4 = _slicedToArray(_useEntityProp3, 2),
      slug = _useEntityProp4[0],
      setSlug = _useEntityProp4[1];

  var _useEntityProp5 = useEntityProp('postType', 'wp_template_part', 'status', postId),
      _useEntityProp6 = _slicedToArray(_useEntityProp5, 2),
      status = _useEntityProp6[0],
      setStatus = _useEntityProp6[1];

  return createElement("div", {
    className: "wp-block-template-part__name-panel"
  }, createElement(TextControl, {
    label: __('Name'),
    value: title || slug,
    onChange: function onChange(value) {
      setTitle(value);
      var newSlug = cleanForSlug(value);
      setSlug(newSlug);

      if (status !== 'publish') {
        setStatus('publish');
      }

      setAttributes({
        slug: newSlug,
        postId: postId
      });
    },
    onFocus: function onFocus(event) {
      return event.target.select();
    }
  }));
}
//# sourceMappingURL=name-panel.js.map