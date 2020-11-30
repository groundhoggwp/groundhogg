/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { shortcode as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import edit from './edit';
import save from './save';
import transforms from './transforms';
var metadata = {
  name: "core/shortcode",
  category: "widgets",
  attributes: {
    text: {
      type: "string",
      source: "html"
    }
  },
  supports: {
    className: false,
    customClassName: false,
    html: false
  }
};
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Shortcode'),
  description: __('Insert additional custom elements with a WordPress shortcode.'),
  icon: icon,
  transforms: transforms,
  edit: edit,
  save: save
};
//# sourceMappingURL=index.js.map