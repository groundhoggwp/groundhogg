/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { postList as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import deprecated from './deprecated';
import edit from './edit';
var metadata = {
  name: "core/latest-posts",
  category: "widgets",
  attributes: {
    categories: {
      type: "array",
      items: {
        type: "object"
      }
    },
    selectedAuthor: {
      type: "number"
    },
    postsToShow: {
      type: "number",
      "default": 5
    },
    displayPostContent: {
      type: "boolean",
      "default": false
    },
    displayPostContentRadio: {
      type: "string",
      "default": "excerpt"
    },
    excerptLength: {
      type: "number",
      "default": 55
    },
    displayAuthor: {
      type: "boolean",
      "default": false
    },
    displayPostDate: {
      type: "boolean",
      "default": false
    },
    postLayout: {
      type: "string",
      "default": "list"
    },
    columns: {
      type: "number",
      "default": 3
    },
    order: {
      type: "string",
      "default": "desc"
    },
    orderBy: {
      type: "string",
      "default": "date"
    },
    displayFeaturedImage: {
      type: "boolean",
      "default": false
    },
    featuredImageAlign: {
      type: "string",
      "enum": ["left", "center", "right"]
    },
    featuredImageSizeSlug: {
      type: "string",
      "default": "thumbnail"
    },
    featuredImageSizeWidth: {
      type: "number",
      "default": null
    },
    featuredImageSizeHeight: {
      type: "number",
      "default": null
    },
    addLinkToFeaturedImage: {
      type: "boolean",
      "default": false
    }
  },
  supports: {
    align: true,
    html: false
  }
};
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Latest Posts'),
  description: __('Display a list of your most recent posts.'),
  icon: icon,
  keywords: [__('recent posts')],
  example: {},
  edit: edit,
  deprecated: deprecated
};
//# sourceMappingURL=index.js.map