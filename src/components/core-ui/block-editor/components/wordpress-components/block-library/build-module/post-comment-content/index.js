/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { alignJustify as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

var metadata = {
  name: "core/post-comment-content",
  category: "design",
  usesContext: ["commentId"],
  supports: {
    html: false
  }
};
import edit from './edit';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Post Comment Content'),
  description: __('Post Comment Content'),
  icon: icon,
  edit: edit,
  parent: ['core/post-comment']
};
//# sourceMappingURL=index.js.map