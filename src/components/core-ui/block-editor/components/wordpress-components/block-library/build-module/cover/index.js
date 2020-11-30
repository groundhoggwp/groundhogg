/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { cover as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import deprecated from './deprecated';
import edit from './edit';
var metadata = {
  name: "core/cover",
  category: "media",
  attributes: {
    url: {
      type: "string"
    },
    id: {
      type: "number"
    },
    hasParallax: {
      type: "boolean",
      "default": false
    },
    dimRatio: {
      type: "number",
      "default": 50
    },
    overlayColor: {
      type: "string"
    },
    customOverlayColor: {
      type: "string"
    },
    backgroundType: {
      type: "string",
      "default": "image"
    },
    focalPoint: {
      type: "object"
    },
    minHeight: {
      type: "number"
    },
    minHeightUnit: {
      type: "string"
    },
    gradient: {
      type: "string"
    },
    customGradient: {
      type: "string"
    },
    contentPosition: {
      type: "string"
    }
  },
  supports: {
    anchor: true,
    align: true,
    html: false,
    lightBlockWrapper: true,
    __experimentalPadding: true
  }
};
import save from './save';
import transforms from './transforms';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Cover'),
  description: __('Add an image or video with a text overlay — great for headers.'),
  icon: icon,
  example: {
    attributes: {
      customOverlayColor: '#065174',
      dimRatio: 40,
      url: 'https://s.w.org/images/core/5.3/Windbuchencom.jpg'
    },
    innerBlocks: [{
      name: 'core/paragraph',
      attributes: {
        customFontSize: 48,
        content: __('<strong>Snow Patrol</strong>'),
        align: 'center'
      }
    }]
  },
  transforms: transforms,
  save: save,
  edit: edit,
  deprecated: deprecated
};
//# sourceMappingURL=index.js.map