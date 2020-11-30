/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { postComments as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

var metadata = {
  name: "core/post-comments",
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
    align: ["wide", "full"],
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
  title: __('Post Comments'),
  description: __("Display a post's comments."),
  icon: icon,
  edit: edit
};
//# sourceMappingURL=index.js.map