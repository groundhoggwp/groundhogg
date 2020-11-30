/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { postExcerpt as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

var metadata = {
  name: "core/post-excerpt",
  category: "design",
  attributes: {
    textAlign: {
      type: "string"
    },
    wordCount: {
      type: "number",
      "default": 55
    },
    moreText: {
      type: "string"
    },
    showMoreOnNewLine: {
      type: "boolean",
      "default": true
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
  title: __('Post Excerpt'),
  description: __("Display a post's excerpt."),
  icon: icon,
  edit: edit
};
//# sourceMappingURL=index.js.map