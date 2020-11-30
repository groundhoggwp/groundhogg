/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { postDate as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

var metadata = {
  name: "core/post-comment-date",
  category: "design",
  attributes: {
    format: {
      type: "string"
    }
  },
  usesContext: ["commentId"],
  supports: {
    html: false
  }
};
import edit from './edit';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Post Comment Date'),
  description: __('Post Comment Date'),
  icon: icon,
  edit: edit,
  parent: ['core/post-comment']
};
//# sourceMappingURL=index.js.map