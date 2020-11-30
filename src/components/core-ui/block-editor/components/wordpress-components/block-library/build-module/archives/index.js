/**
 * WordPress dependencies
 */
import { archive as icon } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

var metadata = {
  name: "core/archives",
  category: "widgets",
  attributes: {
    displayAsDropdown: {
      type: "boolean",
      "default": false
    },
    showPostCounts: {
      type: "boolean",
      "default": false
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
  title: __('Archives'),
  description: __('Display a monthly archive of your posts.'),
  icon: icon,
  example: {},
  edit: edit
};
//# sourceMappingURL=index.js.map