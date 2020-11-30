/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { tag as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

var metadata = {
  name: "core/tag-cloud",
  category: "widgets",
  attributes: {
    taxonomy: {
      type: "string",
      "default": "post_tag"
    },
    showTagCounts: {
      type: "boolean",
      "default": false
    }
  },
  supports: {
    html: false,
    align: true
  }
};
import edit from './edit';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Tag Cloud'),
  description: __('A cloud of your most used tags.'),
  icon: icon,
  example: {},
  edit: edit
};
//# sourceMappingURL=index.js.map