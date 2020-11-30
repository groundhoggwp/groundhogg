/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { postCommentsCount as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

var metadata = {
  name: "core/post-comments-count",
  category: "design",
  attributes: {
    textAlign: {
      type: "string"
    }
  },
  usesContext: ["postId"],
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
  title: __('Post Comments Count'),
  description: __("Display a post's comments count."),
  icon: icon,
  edit: edit
};
//# sourceMappingURL=index.js.map