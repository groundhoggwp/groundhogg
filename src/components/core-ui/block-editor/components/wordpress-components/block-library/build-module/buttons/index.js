/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { button as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import transforms from './transforms';
import edit from './edit';
var metadata = {
  name: "core/buttons",
  category: "design",
  supports: {
    anchor: true,
    align: true,
    alignWide: false,
    lightBlockWrapper: true
  }
};
import save from './save';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Buttons'),
  description: __('Prompt visitors to take action with a group of button-style links.'),
  icon: icon,
  keywords: [__('link')],
  example: {
    innerBlocks: [{
      name: 'core/button',
      attributes: {
        text: __('Find out more')
      }
    }, {
      name: 'core/button',
      attributes: {
        text: __('Contact us')
      }
    }]
  },
  transforms: transforms,
  edit: edit,
  save: save
};
//# sourceMappingURL=index.js.map