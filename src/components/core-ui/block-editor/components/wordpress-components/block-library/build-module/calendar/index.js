/**
 * WordPress dependencies
 */
import { calendar as icon } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

var metadata = {
  name: "core/calendar",
  category: "widgets",
  attributes: {
    month: {
      type: "integer"
    },
    year: {
      type: "integer"
    }
  },
  supports: {
    align: true
  }
};
import edit from './edit';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Calendar'),
  description: __('A calendar of your site’s posts.'),
  icon: icon,
  keywords: [__('posts'), __('archive')],
  example: {},
  edit: edit
};
//# sourceMappingURL=index.js.map