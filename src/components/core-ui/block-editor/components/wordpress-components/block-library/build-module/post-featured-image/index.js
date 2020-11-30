/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { postFeaturedImage as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

var metadata = {
  name: "core/post-featured-image",
  category: "design",
  usesContext: ["postId"],
  supports: {
    html: false
  }
};
import edit from './edit';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Post Featured Image'),
  description: __("Display a post's featured image."),
  icon: icon,
  edit: edit
};
//# sourceMappingURL=index.js.map