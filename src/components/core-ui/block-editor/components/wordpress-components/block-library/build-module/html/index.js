/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { html as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import edit from './edit';
var metadata = {
  name: "core/html",
  category: "widgets",
  attributes: {
    content: {
      type: "string",
      source: "html"
    }
  },
  supports: {
    customClassName: false,
    className: false,
    html: false
  }
};
import save from './save';
import transforms from './transforms';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Custom HTML'),
  description: __('Add custom HTML code and preview it as you edit.'),
  icon: icon,
  keywords: [__('embed')],
  example: {
    attributes: {
      content: '<marquee>' + __('Welcome to the wonderful world of blocks…') + '</marquee>'
    }
  },
  edit: edit,
  save: save,
  transforms: transforms
};
//# sourceMappingURL=index.js.map