/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { postCommentsForm as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

var metadata = {
  name: "core/post-comments-form",
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
    __experimentalColor: {
      gradients: true,
      linkColor: true
    },
    __experimentalFontSize: true,
    __experimentalLineHeight: true
  }
};
import edit from './edit';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Post Comments Form'),
  description: __("Display a post's comments form."),
  icon: icon,
  edit: edit
};
//# sourceMappingURL=index.js.map