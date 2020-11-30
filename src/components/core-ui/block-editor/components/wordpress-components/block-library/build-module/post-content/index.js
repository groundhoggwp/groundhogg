/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { alignJustify as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

var metadata = {
  name: "core/post-content",
  category: "design",
  usesContext: ["postId", "postType"],
  supports: {
    align: ["wide", "full"],
    html: false
  }
};
import edit from './edit';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Post Content'),
  icon: icon,
  edit: edit
};
//# sourceMappingURL=index.js.map