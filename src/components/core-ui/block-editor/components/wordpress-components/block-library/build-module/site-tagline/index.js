/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

var metadata = {
  name: "core/site-tagline",
  category: "design",
  attributes: {
    textAlign: {
      type: "string"
    }
  },
  supports: {
    html: false,
    lightBlockWrapper: true,
    __experimentalColor: {
      gradients: true
    },
    __experimentalFontSize: true,
    __experimentalLineHeight: true
  }
};
import edit from './edit';
import icon from './icon';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Site Tagline'),
  keywords: [__('description')],
  icon: icon,
  edit: edit
};
//# sourceMappingURL=index.js.map