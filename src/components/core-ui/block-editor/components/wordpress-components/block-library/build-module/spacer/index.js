/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { resizeCornerNE as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import edit from './edit';
var metadata = {
  name: "core/spacer",
  category: "design",
  attributes: {
    height: {
      type: "number",
      "default": 100
    }
  },
  supports: {
    anchor: true
  }
};
import save from './save';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Spacer'),
  description: __('Add white space between blocks and customize its height.'),
  icon: icon,
  edit: edit,
  save: save
};
//# sourceMappingURL=index.js.map