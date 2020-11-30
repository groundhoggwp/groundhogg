/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { gallery as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import deprecated from './deprecated';
import edit from './edit';
var metadata = {
  name: "core/gallery",
  category: "media",
  attributes: {
    images: {
      type: "array",
      "default": [],
      source: "query",
      selector: ".blocks-gallery-item",
      query: {
        url: {
          type: "string",
          source: "attribute",
          selector: "img",
          attribute: "src"
        },
        fullUrl: {
          type: "string",
          source: "attribute",
          selector: "img",
          attribute: "data-full-url"
        },
        link: {
          type: "string",
          source: "attribute",
          selector: "img",
          attribute: "data-link"
        },
        alt: {
          type: "string",
          source: "attribute",
          selector: "img",
          attribute: "alt",
          "default": ""
        },
        id: {
          type: "string",
          source: "attribute",
          selector: "img",
          attribute: "data-id"
        },
        caption: {
          type: "string",
          source: "html",
          selector: ".blocks-gallery-item__caption"
        }
      }
    },
    ids: {
      type: "array",
      items: {
        type: "number"
      },
      "default": []
    },
    columns: {
      type: "number",
      minimum: 1,
      maximum: 8
    },
    caption: {
      type: "string",
      source: "html",
      selector: ".blocks-gallery-caption"
    },
    imageCrop: {
      type: "boolean",
      "default": true
    },
    linkTo: {
      type: "string"
    },
    sizeSlug: {
      type: "string",
      "default": "large"
    }
  },
  supports: {
    anchor: true,
    align: true
  }
};
import save from './save';
import transforms from './transforms';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Gallery'),
  description: __('Display multiple images in a rich gallery.'),
  icon: icon,
  keywords: [__('images'), __('photos')],
  example: {
    attributes: {
      columns: 2,
      images: [{
        url: 'https://s.w.org/images/core/5.3/Glacial_lakes%2C_Bhutan.jpg'
      }, {
        url: 'https://s.w.org/images/core/5.3/Sediment_off_the_Yucatan_Peninsula.jpg'
      }]
    }
  },
  transforms: transforms,
  edit: edit,
  save: save,
  deprecated: deprecated
};
//# sourceMappingURL=index.js.map