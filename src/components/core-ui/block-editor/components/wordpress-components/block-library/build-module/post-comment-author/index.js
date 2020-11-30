/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

var metadata = {
  name: "core/post-comment-author",
  category: "design",
  usesContext: ["commentId"],
  supports: {
    html: false
  }
};
import edit from './edit';
import icon from './icon';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Post Comment Author'),
  description: __('Post Comment Author'),
  icon: icon,
  edit: edit,
  parent: ['core/post-comment']
};
//# sourceMappingURL=index.js.map