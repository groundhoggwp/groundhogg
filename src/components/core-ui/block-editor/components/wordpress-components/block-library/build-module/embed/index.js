/**
 * Internal dependencies
 */
import edit from './edit';
import save from './save';
var metadata = {
  name: "core/embed",
  category: "embed",
  attributes: {
    url: {
      type: "string"
    },
    caption: {
      type: "string",
      source: "html",
      selector: "figcaption"
    },
    type: {
      type: "string"
    },
    providerNameSlug: {
      type: "string"
    },
    allowResponsive: {
      type: "boolean",
      "default": true
    },
    responsive: {
      type: "boolean",
      "default": false
    },
    previewable: {
      type: "boolean",
      "default": true
    }
  },
  supports: {
    align: true,
    reusable: false,
    html: false
  }
};
import transforms from './transforms';
import variations from './variations';
import deprecated from './deprecated';
import { embedContentIcon } from './icons';
/**
 * WordPress dependencies
 */

import { __, _x } from '@wordpress/i18n';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: _x('Embed', 'block title'),
  description: __('Add a block that displays content pulled from other sites, like Twitter, Instagram or YouTube.'),
  icon: embedContentIcon,
  edit: edit,
  save: save,
  transforms: transforms,
  variations: variations,
  deprecated: deprecated
};
//# sourceMappingURL=index.js.map