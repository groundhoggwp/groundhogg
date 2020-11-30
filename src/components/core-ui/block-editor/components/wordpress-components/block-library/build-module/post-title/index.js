/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { postTitle as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

var metadata = {
  name: "core/post-title",
  category: "design",
  usesContext: ["postId", "postType"],
  attributes: {
    textAlign: {
      type: "string"
    },
    level: {
      type: "number",
      "default": 2
    },
    isLink: {
      type: "boolean",
      "default": false
    },
    rel: {
      type: "string",
      attribute: "rel",
      "default": ""
    },
    linkTarget: {
      type: "string",
      "default": "_blank"
    }
  },
  supports: {
    html: false,
    lightBlockWrapper: true,
    __experimentalColor: {
      gradients: true
    },
    __experimentalFontSize: true,
    __experimentalLineHeight: true,
    __experimentalSelector: {
      "core/post-title/h1": "h1",
      "core/post-title/h2": "h2",
      "core/post-title/h3": "h3",
      "core/post-title/h4": "h4",
      "core/post-title/h5": "h5",
      "core/post-title/h6": "h6"
    }
  }
};
import edit from './edit';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Post Title'),
  description: __('Add the title of your post.'),
  icon: icon,
  edit: edit
};
//# sourceMappingURL=index.js.map