/**
 * WordPress dependencies
 */
import { __, _x } from '@wordpress/i18n';
import { more as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import edit from './edit';
var metadata = {
  name: "core/more",
  category: "design",
  attributes: {
    customText: {
      type: "string"
    },
    noTeaser: {
      type: "boolean",
      "default": false
    }
  },
  supports: {
    customClassName: false,
    className: false,
    html: false,
    multiple: false
  }
};
import save from './save';
import transforms from './transforms';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: _x('More', 'block name'),
  description: __('Content before this block will be shown in the excerpt on your archives page.'),
  keywords: [__('read more')],
  icon: icon,
  example: {},
  __experimentalLabel: function __experimentalLabel(attributes, _ref) {
    var context = _ref.context;

    if (context === 'accessibility') {
      return attributes.customText;
    }
  },
  transforms: transforms,
  edit: edit,
  save: save
};
//# sourceMappingURL=index.js.map