/**
 * WordPress dependencies
 */
import { category as icon } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

var metadata = {
  name: "core/categories",
  category: "widgets",
  attributes: {
    displayAsDropdown: {
      type: "boolean",
      "default": false
    },
    showHierarchy: {
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
  title: __('Categories'),
  description: __('Display a list of all categories.'),
  icon: icon,
  example: {},
  edit: edit
};
//# sourceMappingURL=index.js.map