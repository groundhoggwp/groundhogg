/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { comment as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

var metadata = {
  name: "core/post-comment",
  category: "design",
  attributes: {
    commentId: {
      type: "number"
    }
  },
  providesContext: {
    commentId: "commentId"
  },
  supports: {
    html: false
  }
};
import edit from './edit';
import save from './save';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Post Comment'),
  description: __('Post Comment'),
  icon: icon,
  edit: edit,
  save: save
};
//# sourceMappingURL=index.js.map