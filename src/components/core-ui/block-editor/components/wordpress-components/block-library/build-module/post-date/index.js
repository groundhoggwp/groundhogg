/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { postDate as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

var metadata = {
  name: "core/post-date",
  category: "design",
  attributes: {
    textAlign: {
      type: "string"
    },
    format: {
      type: "string"
    }
  },
  usesContext: ["postId", "postType"],
  supports: {
    html: false,
    lightBlockWrapper: true,
    __experimentalColor: {
      gradients: true
    },
    __experimentalFontSize: true,
    __experimentalLineHeight: true
  }
};
import edit from './edit';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Post Date'),
  description: __('Add the date of this post.'),
  icon: icon,
  edit: edit
};
//# sourceMappingURL=index.js.map