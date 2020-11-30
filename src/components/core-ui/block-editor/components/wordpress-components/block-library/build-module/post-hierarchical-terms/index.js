/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

var metadata = {
  name: "core/post-hierarchical-terms",
  category: "design",
  attributes: {
    term: {
      type: "string"
    },
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
import variations from './variations';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Post Hierarchical Terms'),
  variations: variations,
  edit: edit
};
//# sourceMappingURL=index.js.map