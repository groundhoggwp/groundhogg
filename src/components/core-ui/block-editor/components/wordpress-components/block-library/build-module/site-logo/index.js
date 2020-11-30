/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

var metadata = {
  name: "core/site-logo",
  category: "layout",
  attributes: {
    align: {
      type: "string"
    },
    width: {
      type: "number"
    }
  },
  supports: {
    html: false,
    lightBlockWrapper: true
  }
};
import icon from './icon';
import edit from './edit';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Site Logo'),
  description: __('Show a site logo'),
  icon: icon,
  supports: {
    align: true,
    alignWide: false
  },
  edit: edit
};
//# sourceMappingURL=index.js.map