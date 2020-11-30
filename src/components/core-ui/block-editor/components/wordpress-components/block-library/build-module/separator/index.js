/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { separator as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import edit from './edit';
var metadata = {
  name: "core/separator",
  category: "design",
  attributes: {
    color: {
      type: "string"
    },
    customColor: {
      type: "string"
    }
  },
  supports: {
    anchor: true
  }
};
import save from './save';
import transforms from './transforms';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Separator'),
  description: __('Create a break between ideas or sections with a horizontal separator.'),
  icon: icon,
  keywords: [__('horizontal-line'), 'hr', __('divider')],
  example: {
    attributes: {
      customColor: '#065174',
      className: 'is-style-wide'
    }
  },
  styles: [{
    name: 'default',
    label: __('Default'),
    isDefault: true
  }, {
    name: 'wide',
    label: __('Wide Line')
  }, {
    name: 'dots',
    label: __('Dots')
  }],
  transforms: transforms,
  edit: edit,
  save: save
};
//# sourceMappingURL=index.js.map