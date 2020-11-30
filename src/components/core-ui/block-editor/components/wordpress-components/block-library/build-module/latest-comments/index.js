/**
 * WordPress dependencies
 */
import { comment as icon } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

var metadata = {
  name: "core/latest-comments",
  category: "widgets",
  attributes: {
    commentsToShow: {
      type: "number",
      "default": 5,
      minimum: 1,
      maximum: 100
    },
    displayAvatar: {
      type: "boolean",
      "default": true
    },
    displayDate: {
      type: "boolean",
      "default": true
    },
    displayExcerpt: {
      type: "boolean",
      "default": true
    }
  },
  supports: {
    align: true,
    html: false
  }
};
import edit from './edit';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Latest Comments'),
  description: __('Display a list of your most recent comments.'),
  icon: icon,
  keywords: [__('recent comments')],
  example: {},
  edit: edit
};
//# sourceMappingURL=index.js.map