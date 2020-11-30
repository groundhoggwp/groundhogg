/**
 * WordPress dependencies
 */
import { rss as icon } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

var metadata = {
  name: "core/rss",
  category: "widgets",
  attributes: {
    columns: {
      type: "number",
      "default": 2
    },
    blockLayout: {
      type: "string",
      "default": "list"
    },
    feedURL: {
      type: "string",
      "default": ""
    },
    itemsToShow: {
      type: "number",
      "default": 5
    },
    displayExcerpt: {
      type: "boolean",
      "default": false
    },
    displayAuthor: {
      type: "boolean",
      "default": false
    },
    displayDate: {
      type: "boolean",
      "default": false
    },
    excerptLength: {
      type: "number",
      "default": 55
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
  title: __('RSS'),
  description: __('Display entries from any RSS or Atom feed.'),
  icon: icon,
  keywords: [__('atom'), __('feed')],
  example: {
    attributes: {
      feedURL: 'https://wordpress.org'
    }
  },
  edit: edit
};
//# sourceMappingURL=index.js.map