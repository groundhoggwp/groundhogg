/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { search as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

var metadata = {
  name: "core/search",
  category: "widgets",
  attributes: {
    label: {
      type: "string"
    },
    showLabel: {
      type: "bool",
      "default": true
    },
    placeholder: {
      type: "string",
      "default": ""
    },
    width: {
      type: "number"
    },
    widthUnit: {
      type: "string"
    },
    buttonText: {
      type: "string"
    },
    buttonPosition: {
      type: "string",
      "default": "button-outside"
    },
    buttonUseIcon: {
      type: "bool",
      "default": false
    }
  },
  supports: {
    align: ["left", "center", "right"],
    html: false,
    lightBlockWrapper: true
  }
};
import edit from './edit';
import variations from './variations';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Search'),
  description: __('Help visitors find your content.'),
  icon: icon,
  keywords: [__('find')],
  example: {},
  variations: variations,
  edit: edit
};
//# sourceMappingURL=index.js.map