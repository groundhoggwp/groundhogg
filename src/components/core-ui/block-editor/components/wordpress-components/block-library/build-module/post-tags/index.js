/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { tag as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

var metadata = {
  name: "core/post-tags",
  category: "design",
  attributes: {
    textAlign: {
      type: "string"
    }
  },
  usesContext: ["postId", "postType"],
  supports: {
    html: false,
    lightBlockWrapper: true,
    __experimentalFontSize: true,
    __experimentalColor: {
      gradients: true,
      linkColor: true
    },
    __experimentalLineHeight: true
  }
};
import edit from './edit';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Post Tags'),
  description: __("Display a post's tags."),
  icon: icon,
  edit: edit
};
//# sourceMappingURL=index.js.map