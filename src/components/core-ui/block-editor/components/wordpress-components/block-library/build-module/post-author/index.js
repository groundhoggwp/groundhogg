/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

var metadata = {
  name: "core/post-author",
  category: "design",
  attributes: {
    textAlign: {
      type: "string"
    },
    avatarSize: {
      type: "number",
      "default": 48
    },
    showAvatar: {
      type: "boolean",
      "default": true
    },
    showBio: {
      type: "boolean"
    },
    byline: {
      type: "string"
    }
  },
  usesContext: ["postType", "postId"],
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
import icon from './icon';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Post Author'),
  description: __('Add the author of this post.'),
  icon: icon,
  edit: edit
};
//# sourceMappingURL=index.js.map